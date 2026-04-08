<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SupportTicket\StoreSupportTicketMessageRequest;
use App\Http\Requests\Api\SupportTicket\StoreSupportTicketRequest;
use App\Models\AppUser;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppUserSupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $tickets = SupportTicket::query()
            ->where('app_user_id', $appUser->id)
            ->with([
                'appUser:id,name,username,profile_image',
                'assignedUser:id,name,phone',
                'latestMessage.senderUser:id,name',
                'latestMessage.senderAppUser:id,name,username,profile_image',
            ])
            ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->formatTicketSummary($ticket, $appUser));

        return response()->json([
            'status' => true,
            'data' => $tickets,
        ]);
    }

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $data = $request->validated();

        $ticket = DB::transaction(function () use ($appUser, $data) {
            $ticket = SupportTicket::query()->create([
                'ticket_number' => $this->generateTicketNumber(),
                'app_user_id' => $appUser->id,
                'created_by_app_user_id' => $appUser->id,
                'subject' => $data['subject'],
                'status' => SupportTicket::STATUS_OPEN,
            ]);

            $message = $ticket->messages()->create([
                'sender_app_user_id' => $appUser->id,
                'body' => $data['message'],
            ]);

            $ticket->update([
                'last_message_at' => $message->created_at,
            ]);

            return $ticket->load([
                'appUser:id,name,username,profile_image',
                'assignedUser:id,name,phone',
                'messages.senderUser:id,name',
                'messages.senderAppUser:id,name,username,profile_image',
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Support ticket created successfully',
            'data' => $this->formatTicketDetails($ticket, $appUser),
        ], 201);
    }

    public function show(Request $request, int $ticketId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $ticket = $this->getAuthorizedTicket($ticketId, $appUser->id);

        return response()->json([
            'status' => true,
            'data' => $this->formatTicketDetails($ticket, $appUser),
        ]);
    }

    public function storeMessage(StoreSupportTicketMessageRequest $request, int $ticketId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $ticket = $this->getAuthorizedTicket($ticketId, $appUser->id);

        abort_if($ticket->status === SupportTicket::STATUS_CLOSED, 422, 'Closed tickets cannot receive new messages.');

        $message = DB::transaction(function () use ($ticket, $appUser, $request) {
            $message = $ticket->messages()->create([
                'sender_app_user_id' => $appUser->id,
                'body' => $request->validated()['message'],
            ]);

            $ticket->update([
                'last_message_at' => $message->created_at,
            ]);

            return $message->load([
                'senderUser:id,name',
                'senderAppUser:id,name,username,profile_image',
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Support message sent successfully',
            'data' => $this->formatMessage($message, $appUser),
        ], 201);
    }

    public function close(Request $request, int $ticketId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $ticket = $this->getAuthorizedTicket($ticketId, $appUser->id);

        if ($ticket->status !== SupportTicket::STATUS_CLOSED) {
            $ticket->update([
                'status' => SupportTicket::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by_type' => 'app_user',
                'closed_by_id' => $appUser->id,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Support ticket closed successfully',
            'data' => $this->formatTicketDetails(
                $ticket->fresh([
                    'appUser:id,name,username,profile_image',
                    'assignedUser:id,name,phone',
                    'messages.senderUser:id,name',
                    'messages.senderAppUser:id,name,username,profile_image',
                ]),
                $appUser
            ),
        ]);
    }

    public function reopen(Request $request, int $ticketId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $ticket = $this->getAuthorizedTicket($ticketId, $appUser->id);

        $ticket->update([
            'status' => SupportTicket::STATUS_OPEN,
            'closed_at' => null,
            'closed_by_type' => null,
            'closed_by_id' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Support ticket reopened successfully',
            'data' => $this->formatTicketDetails(
                $ticket->fresh([
                    'appUser:id,name,username,profile_image',
                    'assignedUser:id,name,phone',
                    'messages.senderUser:id,name',
                    'messages.senderAppUser:id,name,username,profile_image',
                ]),
                $appUser
            ),
        ]);
    }

    private function getAuthorizedTicket(int $ticketId, int $appUserId): SupportTicket
    {
        return SupportTicket::query()
            ->whereKey($ticketId)
            ->where('app_user_id', $appUserId)
            ->with([
                'appUser:id,name,username,profile_image',
                'assignedUser:id,name,phone',
                'messages.senderUser:id,name',
                'messages.senderAppUser:id,name,username,profile_image',
                'latestMessage.senderUser:id,name',
                'latestMessage.senderAppUser:id,name,username,profile_image',
            ])
            ->firstOrFail();
    }

    private function formatTicketSummary(SupportTicket $ticket, AppUser $authUser): array
    {
        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'app_user' => $this->formatAppUser($ticket->appUser),
            'assigned_user' => $this->formatUser($ticket->assignedUser),
            'last_message' => $ticket->latestMessage
                ? $this->formatMessage($ticket->latestMessage, $authUser)
                : null,
            'last_message_at' => $ticket->last_message_at?->toISOString(),
            'closed_at' => $ticket->closed_at?->toISOString(),
            'created_at' => $ticket->created_at?->toISOString(),
        ];
    }

    private function formatTicketDetails(SupportTicket $ticket, AppUser $authUser): array
    {
        return [
            ...$this->formatTicketSummary($ticket, $authUser),
            'messages' => $ticket->messages
                ->map(fn (SupportTicketMessage $message) => $this->formatMessage($message, $authUser))
                ->values(),
        ];
    }

    private function formatMessage(SupportTicketMessage $message, AppUser $authUser): array
    {
        return [
            'id' => $message->id,
            'ticket_id' => $message->support_ticket_id,
            'body' => $message->body,
            'sender_type' => $message->sender_app_user_id ? 'app_user' : 'user',
            'sender' => $message->sender_app_user_id
                ? $this->formatAppUser($message->senderAppUser)
                : $this->formatUser($message->senderUser),
            'is_mine' => $message->sender_app_user_id === $authUser->id,
            'created_at' => $message->created_at?->toISOString(),
            'created_at_label' => $message->created_at?->format('H:i'),
        ];
    }

    private function formatAppUser(?AppUser $appUser): ?array
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

    private function formatUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
        ];
    }

    private function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'SUP-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (SupportTicket::query()->where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
