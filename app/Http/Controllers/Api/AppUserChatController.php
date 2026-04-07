<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageDeleted;
use App\Events\ChatMessageSent;
use App\Events\ChatMessageUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserChat\StartDirectConversationRequest;
use App\Http\Requests\Api\AppUserChat\StoreMessageRequest;
use App\Http\Requests\Api\AppUserChat\UpdateMessageRequest;
use App\Models\AppUser;
use App\Models\AppUserConversation;
use App\Models\AppUserConversationMessage;
use App\Models\AppUserConversationParticipant;
use App\Models\AppUserDeviceToken;
use App\Models\AppUserNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppUserChatController extends Controller
{
    public function __construct(
        private readonly FirebaseNotificationService $firebaseNotificationService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $conversations = AppUserConversation::query()
            ->whereHas('participants', fn ($query) => $query->where('app_user_id', $appUser->id))
            ->select('app_user_conversations.*')
            ->selectSub(
                AppUserConversationMessage::query()
                    ->select('created_at')
                    ->whereColumn('app_user_conversation_id', 'app_user_conversations.id')
                    ->whereNull('deleted_at')
                    ->latest('created_at')
                    ->limit(1),
                'latest_visible_message_at'
            )
            ->with([
                'participants.appUser:id,name,username,profile_image',
                'latestVisibleMessage.sender:id,name,username,profile_image',
            ])
            ->withCount([
                'messages as unread_messages_count' => function ($query) use ($appUser) {
                    $query
                        ->where('sender_app_user_id', '!=', $appUser->id)
                        ->whereExists(function ($subQuery) use ($appUser) {
                            $subQuery->selectRaw('1')
                                ->from('app_user_conversation_participants as participant')
                                ->whereColumn(
                                    'participant.app_user_conversation_id',
                                    'app_user_conversation_messages.app_user_conversation_id'
                                )
                                ->where('participant.app_user_id', $appUser->id)
                                ->where(function ($nestedQuery) {
                                    $nestedQuery
                                        ->whereNull('participant.last_read_at')
                                        ->orWhereColumn(
                                            'app_user_conversation_messages.created_at',
                                            '>',
                                            'participant.last_read_at'
                                        );
                                });
                        });
                },
            ])
            ->orderByDesc(DB::raw('COALESCE(latest_visible_message_at, created_at)'))
            ->get()
            ->map(fn (AppUserConversation $conversation) => $this->formatConversationSummary($conversation, $appUser));

        return response()->json([
            'status' => true,
            'data' => $conversations,
        ]);
    }

    public function startDirectConversation(StartDirectConversationRequest $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $recipientId = (int) $request->validated()['recipient_app_user_id'];
        $isSelfConversation = $recipientId === (int) $appUser->id;

        $existingConversation = $this->findDirectConversation($appUser->id, $recipientId);

        if ($existingConversation) {
            return response()->json([
                'status' => true,
                'message' => 'Conversation loaded successfully',
                'data' => $this->formatConversationDetails($existingConversation, $appUser),
            ]);
        }

        $conversation = DB::transaction(function () use ($appUser, $recipientId, $isSelfConversation) {
            $conversation = AppUserConversation::query()->create([
                'type' => 'direct',
                'created_by_app_user_id' => $appUser->id,
            ]);

            $participants = [
                [
                    'app_user_id' => $appUser->id,
                    'last_read_at' => now(),
                    'joined_at' => now(),
                ],
            ];

            if (! $isSelfConversation) {
                $participants[] = [
                    'app_user_id' => $recipientId,
                    'joined_at' => now(),
                ];
            }

            $conversation->participants()->createMany($participants);

            return $conversation->fresh([
                'participants.appUser:id,name,username,profile_image',
                'latestVisibleMessage.sender:id,name,username,profile_image',
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Conversation created successfully',
            'data' => $this->formatConversationDetails($conversation, $appUser),
        ], 201);
    }

    public function show(Request $request, int $conversationId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $conversation = $this->getAuthorizedConversation($conversationId, $appUser->id);

        return response()->json([
            'status' => true,
            'data' => $this->formatConversationDetails($conversation, $appUser),
        ]);
    }

    public function messages(Request $request, string $conversationId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $conversationId = (int) $conversationId;
        $conversation = $this->getAuthorizedConversation($conversationId, $appUser->id);
        $latestMessageAt = $conversation->latestVisibleMessage?->created_at;

        if ($latestMessageAt) {
            AppUserConversationParticipant::query()
                ->where('app_user_conversation_id', $conversation->id)
                ->where('app_user_id', $appUser->id)
                ->update([
                    'last_read_at' => $latestMessageAt,
                ]);
        }

        $lastReadAt = $latestMessageAt;

        $messages = $conversation->messages()
            ->with('sender:id,name,username,profile_image')
            ->orderBy('id')
            ->get()
            ->map(fn (AppUserConversationMessage $message) => $this->formatMessage(
                $message,
                $appUser,
                $lastReadAt,
                $conversation->participants
            ));

        return response()->json([
            'status' => true,
            'data' => [
                'conversation' => $this->formatConversationDetails($conversation, $appUser),
                'messages' => $messages,
            ],
        ]);
    }

    public function storeMessage(StoreMessageRequest $request, int $conversationId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $conversation = $this->getAuthorizedConversation($conversationId, $appUser->id);
        $data = $request->validated();

        $message = DB::transaction(function () use ($conversation, $appUser, $data) {
            $message = $conversation->messages()->create([
                'sender_app_user_id' => $appUser->id,
                'type' => $data['type'] ?? 'text',
                'body' => $data['body'],
            ]);

            $conversation->update([
                'last_message_at' => $message->created_at,
            ]);

            AppUserConversationParticipant::query()
                ->where('app_user_conversation_id', $conversation->id)
                ->where('app_user_id', $appUser->id)
                ->update([
                    'last_read_at' => $message->created_at,
                ]);

            return $message->load('sender:id,name,username,profile_image');
        });

        broadcast(new ChatMessageSent($message))->toOthers();
        $this->sendChatMessageNotifications($conversation, $message, $appUser);

        return response()->json([
            'status' => true,
            'message' => 'Message sent successfully',
            'data' => $this->formatMessage($message, $appUser),
        ], 201);
    }

    public function updateMessage(UpdateMessageRequest $request, int $conversationId, int $messageId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $conversation = $this->getAuthorizedConversation($conversationId, $appUser->id);
        $message = $this->getAuthorizedOwnedMessage($conversation->id, $messageId, $appUser->id);

        abort_if($message->deleted_at !== null, 422, 'Deleted messages cannot be updated.');

        $data = $request->validated();

        $message->update([
            'body' => $data['body'],
            'edited_at' => now(),
        ]);

        $message->load('sender:id,name,username,profile_image');

        broadcast(new ChatMessageUpdated($message));

        return response()->json([
            'status' => true,
            'message' => 'Message updated successfully',
            'data' => $this->formatMessage($message, $appUser),
        ]);
    }

    public function destroyMessage(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $conversation = $this->getAuthorizedConversation($conversationId, $appUser->id);
        $message = $this->getAuthorizedOwnedMessage($conversation->id, $messageId, $appUser->id);
        if ($message->deleted_at === null) {
            $message->update([
                'type' => 'deleted',
                'body' => "\u{062A}\u{0645} \u{0645}\u{0633}\u{062D} \u{0627}\u{0644}\u{0631}\u{0633}\u{0627}\u{0644}\u{0629}",
                'deleted_at' => now(),
                'edited_at' => null,
                'meta' => array_merge($message->meta ?? [], [
                    'deleted_for_everyone' => true,
                ]),
            ]);

            $conversation->update([
                'last_message_at' => $conversation->messages()
                    ->whereNull('deleted_at')
                    ->latest('created_at')
                    ->value('created_at'),
            ]);
        }

        $message->load('sender:id,name,username,profile_image');

        broadcast(new ChatMessageDeleted($message));

        return response()->json([
            'status' => true,
            'message' => 'Message deleted successfully',
            'data' => $this->formatMessage($message, $appUser),
        ]);
    }

    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $conversation = $this->getAuthorizedConversation($conversationId, $appUser->id);
        $latestMessageAt = $conversation->latestVisibleMessage?->created_at ?? now();

        AppUserConversationParticipant::query()
            ->where('app_user_conversation_id', $conversation->id)
            ->where('app_user_id', $appUser->id)
            ->update([
                'last_read_at' => $latestMessageAt,
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Conversation marked as read successfully',
        ]);
    }

    private function getAuthorizedConversation(int $conversationId, int $appUserId): AppUserConversation
    {
        $conversation = AppUserConversation::query()
            ->whereKey($conversationId)
            ->whereHas('participants', fn ($query) => $query->where('app_user_id', $appUserId))
            ->with([
                'participants.appUser:id,name,username,profile_image',
                'latestVisibleMessage.sender:id,name,username,profile_image',
            ])
            ->firstOrFail();

        return $conversation;
    }

    private function getAuthorizedOwnedMessage(int $conversationId, int $messageId, int $appUserId): AppUserConversationMessage
    {
        return AppUserConversationMessage::query()
            ->whereKey($messageId)
            ->where('app_user_conversation_id', $conversationId)
            ->where('sender_app_user_id', $appUserId)
            ->with('sender:id,name,username,profile_image')
            ->firstOrFail();
    }

    private function findDirectConversation(int $firstUserId, int $secondUserId): ?AppUserConversation
    {
        $query = AppUserConversation::query()
            ->where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->where('app_user_id', $firstUserId))
            ->with([
                'participants.appUser:id,name,username,profile_image',
                'latestVisibleMessage.sender:id,name,username,profile_image',
            ]);

        if ($firstUserId === $secondUserId) {
            return $query
                ->has('participants', '=', 1)
                ->first();
        }

        return $query
            ->whereHas('participants', fn ($query) => $query->where('app_user_id', $secondUserId))
            ->has('participants', '=', 2)
            ->first();
    }

    private function formatConversationSummary(AppUserConversation $conversation, AppUser $authUser): array
    {
        $currentParticipant = $conversation->participants
            ->firstWhere('app_user_id', $authUser->id);

        $otherParticipant = $conversation->participants
            ->firstWhere('app_user_id', '!=', $authUser->id)
            ?? $currentParticipant;

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'participant' => $otherParticipant ? $this->formatUser($otherParticipant->appUser) : null,
            'last_message' => $conversation->latestVisibleMessage
                ? $this->formatMessage($conversation->latestVisibleMessage, $authUser, null, $conversation->participants)
                : null,
            'unread_messages_count' => (int) ($conversation->unread_messages_count ?? 0),
            'last_read_at' => $currentParticipant?->last_read_at?->toISOString(),
            'last_message_at' => $conversation->latestVisibleMessage?->created_at?->toISOString()
                ?? $conversation->last_message_at?->toISOString(),
            'created_at' => $conversation->created_at?->toISOString(),
        ];
    }

    private function formatConversationDetails(AppUserConversation $conversation, AppUser $authUser): array
    {
        return [
            ...$this->formatConversationSummary($conversation, $authUser),
            'participants' => $conversation->participants
                ->map(fn (AppUserConversationParticipant $participant) => $this->formatUser($participant->appUser))
                ->values(),
        ];
    }

    private function formatMessage(
        AppUserConversationMessage $message,
        AppUser $authUser,
        ?Carbon $lastReadAt = null,
        ?Collection $participants = null
    ): array
    {
        $isReadForCurrentUser = $lastReadAt !== null
            && $message->created_at !== null
            && $message->created_at->lte($lastReadAt);

        $isReadByAllParticipants = $participants instanceof Collection
            && $message->created_at !== null
            && $participants->isNotEmpty()
            && $participants->every(function (AppUserConversationParticipant $participant) use ($message) {
                return $participant->last_read_at !== null
                    && $message->created_at !== null
                    && $participant->last_read_at->gte($message->created_at);
            });

        return [
            'id' => $message->id,
            'conversation_id' => $message->app_user_conversation_id,
            'sender' => $this->formatUser($message->sender),
            'type' => $message->type,
            'body' => $message->body,
            'meta' => $message->meta,
            'is_mine' => $message->sender_app_user_id === $authUser->id,
            'is_read' => $isReadByAllParticipants,
            'is_unread' => ! $isReadForCurrentUser,
            'is_read_by_me' => $isReadForCurrentUser,
            'is_edited' => $message->edited_at !== null,
            'is_deleted' => $message->deleted_at !== null,
            'edited_at' => $message->edited_at?->toISOString(),
            'deleted_at' => $message->deleted_at?->toISOString(),
            'created_at' => $message->created_at?->toISOString(),
            'created_at_label' => $this->formatTimeLabel($message->created_at),
        ];
    }

    private function formatUser(?AppUser $appUser): ?array
    {
        if (! $appUser) {
            return null;
        }

        return [
            'id' => $appUser->id,
            'name' => $appUser->name,
            'username' => $appUser->username,
            'profile_image_url' => $appUser->profile_image_url,
        ];
    }

    private function formatTimeLabel(?Carbon $timestamp): ?string
    {
        return $timestamp?->format('H:i');
    }

    private function sendChatMessageNotifications(
        AppUserConversation $conversation,
        AppUserConversationMessage $message,
        AppUser $sender
    ): void {
        $deviceTokens = AppUserDeviceToken::query()
            ->whereIn(
                'app_user_id',
                AppUserConversationParticipant::query()
                    ->where('app_user_conversation_id', $conversation->id)
                    ->where('app_user_id', '!=', $sender->id)
                    ->pluck('app_user_id')
            )
            ->get();

        foreach ($deviceTokens as $deviceToken) {
            try {
                $payload = [
                    'type' => 'chat_message',
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'sender_app_user_id' => $sender->id,
                    'sender_name' => $sender->name,
                ];

                $result = $this->firebaseNotificationService->sendToToken(
                    $deviceToken->token,
                    $sender->name,
                    "{$sender->name} Sent Message",
                    $payload
                );

                if (! ($result['status'] ?? false)) {
                    continue;
                }

                AppUserNotification::query()->create([
                    'sender_app_user_id' => $sender->id,
                    'recipient_app_user_id' => $deviceToken->app_user_id,
                    'target_fcm_token' => $deviceToken->token,
                    'title' => $sender->name,
                    'body' => "{$sender->name} Sent Message",
                    'data' => $payload,
                    'is_read' => false,
                    'read_at' => null,
                    'sent_at' => now(),
                ]);
            } catch (\Throwable) {
                // Ignore notification failures so sending the chat message still succeeds.
            }
        }
    }
}
