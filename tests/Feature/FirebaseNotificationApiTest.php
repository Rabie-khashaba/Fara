<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Services\FirebaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class FirebaseNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_firebase_notification_is_saved_in_database(): void
    {
        $sender = AppUser::query()->create([
            'name' => 'Sender User',
            'username' => 'senderuser',
            'phone' => '01000000020',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $recipient = AppUser::query()->create([
            'name' => 'Recipient User',
            'username' => 'recipientuser',
            'phone' => '01000000021',
            'password' => 'secret123',
            'is_active' => true,
            'fcm_token' => 'recipient-token',
        ]);

        Sanctum::actingAs($sender);

        $this->mock(FirebaseNotificationService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendToToken')->once()->andReturn([
                'status' => true,
                'message' => 'Notification sent successfully.',
                'data' => ['name' => 'projects/demo/messages/1'],
            ]);
        });

        $this->postJson('/api/app-user/notifications/firebase/send', [
            'token' => 'recipient-token',
            'title' => 'Test Title',
            'body' => 'Test Body',
            'data' => ['type' => 'chat'],
        ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('app_user_notifications', [
            'sender_app_user_id' => $sender->id,
            'recipient_app_user_id' => $recipient->id,
            'target_fcm_token' => 'recipient-token',
            'title' => 'Test Title',
            'body' => 'Test Body',
        ]);
    }

    public function test_bulk_firebase_notifications_are_saved_for_successful_tokens_only(): void
    {
        $sender = AppUser::query()->create([
            'name' => 'Sender User',
            'username' => 'senderuser2',
            'phone' => '01000000022',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $recipientOne = AppUser::query()->create([
            'name' => 'Recipient One',
            'username' => 'recipientone',
            'phone' => '01000000023',
            'password' => 'secret123',
            'is_active' => true,
            'fcm_token' => 'token-1',
        ]);

        $recipientTwo = AppUser::query()->create([
            'name' => 'Recipient Two',
            'username' => 'recipienttwo',
            'phone' => '01000000024',
            'password' => 'secret123',
            'is_active' => true,
            'fcm_token' => 'token-2',
        ]);

        Sanctum::actingAs($sender);

        $this->mock(FirebaseNotificationService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendToTokens')->once()->andReturn([
                'status' => false,
                'message' => 'Some notifications failed.',
                'data' => [
                    'success_count' => 2,
                    'failed_count' => 1,
                    'results' => [
                        ['token' => 'token-1', 'status' => true, 'message' => 'ok'],
                        ['token' => 'token-x', 'status' => false, 'message' => 'failed'],
                        ['token' => 'token-2', 'status' => true, 'message' => 'ok'],
                    ],
                ],
            ]);
        });

        $this->postJson('/api/app-user/notifications/firebase/send-bulk', [
            'tokens' => ['token-1', 'token-x', 'token-2'],
            'title' => 'Bulk Title',
            'body' => 'Bulk Body',
        ])
            ->assertStatus(207)
            ->assertJsonPath('status', false);

        $this->assertDatabaseHas('app_user_notifications', [
            'sender_app_user_id' => $sender->id,
            'recipient_app_user_id' => $recipientOne->id,
            'target_fcm_token' => 'token-1',
            'title' => 'Bulk Title',
            'body' => 'Bulk Body',
        ]);

        $this->assertDatabaseHas('app_user_notifications', [
            'sender_app_user_id' => $sender->id,
            'recipient_app_user_id' => $recipientTwo->id,
            'target_fcm_token' => 'token-2',
            'title' => 'Bulk Title',
            'body' => 'Bulk Body',
        ]);

        $this->assertDatabaseMissing('app_user_notifications', [
            'target_fcm_token' => 'token-x',
            'title' => 'Bulk Title',
        ]);
    }
}
