<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\AppUserDeviceToken;
use App\Models\AppUserNotification;

class AppUserPushNotificationService
{
    public function __construct(
        private readonly FirebaseNotificationService $firebaseNotificationService
    ) {
    }

    public function sendToUser(
        AppUser $recipient,
        ?AppUser $sender,
        string $title,
        string $body,
        array $data = []
    ): void {
        if ($sender && (int) $sender->id === (int) $recipient->id) {
            return;
        }

        $deviceTokens = AppUserDeviceToken::query()
            ->where('app_user_id', $recipient->id)
            ->get();

        foreach ($deviceTokens as $deviceToken) {
            try {
                $result = $this->firebaseNotificationService->sendToToken(
                    $deviceToken->token,
                    $title,
                    $body,
                    $data
                );

                if (! ($result['status'] ?? false)) {
                    continue;
                }

                AppUserNotification::query()->create([
                    'sender_app_user_id' => $sender?->id,
                    'recipient_app_user_id' => $recipient->id,
                    'target_fcm_token' => $deviceToken->token,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data ?: null,
                    'is_read' => false,
                    'read_at' => null,
                    'sent_at' => now(),
                ]);
            } catch (\Throwable) {
                // Notification failures should not block the main action.
            }
        }
    }
}
