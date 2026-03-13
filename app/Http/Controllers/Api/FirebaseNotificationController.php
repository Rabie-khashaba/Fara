<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\AppUserNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FirebaseNotificationController extends Controller
{
    public function __construct(
        private readonly FirebaseNotificationService $firebaseNotificationService
    ) {
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'data' => ['nullable', 'array'],
        ]);

        try {
            $result = $this->firebaseNotificationService->sendToToken(
                $validated['token'],
                $validated['title'],
                $validated['body'],
                $validated['data'] ?? []
            );

            if ($result['status']) {
                $this->storeNotification(
                    $request->user(),
                    $validated['token'],
                    $validated['title'],
                    $validated['body'],
                    $validated['data'] ?? []
                );
            }
        } catch (\Throwable $throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error while sending notification.',
                'error' => $throwable->getMessage(),
            ], 500);
        }

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    public function sendBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tokens' => ['required', 'array', 'min:1', 'max:500'],
            'tokens.*' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'data' => ['nullable', 'array'],
        ]);

        try {
            $result = $this->firebaseNotificationService->sendToTokens(
                $validated['tokens'],
                $validated['title'],
                $validated['body'],
                $validated['data'] ?? []
            );

            $this->storeBulkNotifications(
                $request->user(),
                $validated['title'],
                $validated['body'],
                $validated['data'] ?? [],
                $result['data']['results'] ?? []
            );
        } catch (\Throwable $throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error while sending notifications.',
                'error' => $throwable->getMessage(),
            ], 500);
        }

        return response()->json($result, $result['status'] ? 200 : 207);
    }

    public function updateToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $user->update([
            'fcm_token' => $validated['fcm_token'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FCM token updated successfully.',
            'data' => [
                'user_id' => $user->id,
                'has_fcm_token' => !empty($user->fcm_token),
            ],
        ]);
    }

    public function myNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $notifications = AppUserNotification::query()
            ->with('sender:id,name,username,profile_image')
            ->where('recipient_app_user_id', $user->id)
            ->latest('sent_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Notifications fetched successfully.',
            'data' => $notifications,
            'meta' => [
                'unread_count' => AppUserNotification::query()
                    ->where('recipient_app_user_id', $user->id)
                    ->where('is_read', false)
                    ->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, AppUserNotification $notification): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ((int) $notification->recipient_app_user_id !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read.',
            'data' => $notification->fresh(),
        ]);
    }

    private function storeNotification(
        ?AppUser $sender,
        string $token,
        string $title,
        string $body,
        array $data = []
    ): void {
        $recipient = AppUser::query()->where('fcm_token', $token)->first();

        AppUserNotification::query()->create([
            'sender_app_user_id' => $sender?->id,
            'recipient_app_user_id' => $recipient?->id,
            'target_fcm_token' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
            'is_read' => false,
            'read_at' => null,
            'sent_at' => now(),
        ]);
    }

    private function storeBulkNotifications(
        ?AppUser $sender,
        string $title,
        string $body,
        array $data,
        array $results
    ): void {
        foreach ($results as $result) {
            if (! ($result['status'] ?? false) || empty($result['token'])) {
                continue;
            }

            $this->storeNotification(
                $sender,
                (string) $result['token'],
                $title,
                $body,
                $data
            );
        }
    }
}
