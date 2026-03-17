<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\AppUserDeviceToken;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_all_sends_notifications_to_all_users_with_fcm_tokens(): void
    {
        $admin = User::factory()->create();

        $sender = AppUser::query()->create([
            'name' => 'Sender User',
            'username' => 'sender-web',
            'phone' => '01000001000',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $recipientOne = AppUser::query()->create([
            'name' => 'Recipient One',
            'username' => 'recipient-web-1',
            'phone' => '01000001001',
            'password' => 'secret123',
            'is_active' => true,
            'fcm_token' => 'legacy-token-1',
        ]);

        $recipientTwo = AppUser::query()->create([
            'name' => 'Recipient Two',
            'username' => 'recipient-web-2',
            'phone' => '01000001002',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $recipientWithoutToken = AppUser::query()->create([
            'name' => 'Recipient Three',
            'username' => 'recipient-web-3',
            'phone' => '01000001003',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        AppUserDeviceToken::query()->create([
            'app_user_id' => $recipientTwo->id,
            'token' => 'device-token-2',
            'token_hash' => AppUserDeviceToken::makeTokenHash('device-token-2'),
        ]);

        $this->actingAs($admin);

        $this->mock(FirebaseNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendToTokens')->once()->withArgs(function (array $tokens, string $title, string $body, array $data): bool {
                sort($tokens);

                return $tokens === ['device-token-2', 'legacy-token-1']
                    && $title === 'Hello'
                    && $body === 'Broadcast'
                    && $data === [];
            })->andReturn([
                'status' => true,
                'message' => 'All notifications sent successfully.',
                'data' => [
                    'success_count' => 2,
                    'failed_count' => 0,
                    'results' => [
                        ['token' => 'legacy-token-1', 'status' => true, 'message' => 'ok'],
                        ['token' => 'device-token-2', 'status' => true, 'message' => 'ok'],
                    ],
                ],
            ]);
        });

        $this->post('/notifications/send-all', [
            'sender_app_user_id' => $sender->id,
            'title' => 'Hello',
            'body' => 'Broadcast',
        ])->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('app_user_notifications', [
            'sender_app_user_id' => $sender->id,
            'recipient_app_user_id' => $recipientOne->id,
            'target_fcm_token' => 'legacy-token-1',
            'title' => 'Hello',
            'body' => 'Broadcast',
        ]);

        $this->assertDatabaseHas('app_user_notifications', [
            'sender_app_user_id' => $sender->id,
            'recipient_app_user_id' => $recipientTwo->id,
            'target_fcm_token' => 'device-token-2',
            'title' => 'Hello',
            'body' => 'Broadcast',
        ]);

        $this->assertDatabaseMissing('app_user_notifications', [
            'recipient_app_user_id' => $recipientWithoutToken->id,
            'title' => 'Hello',
        ]);
    }
}
