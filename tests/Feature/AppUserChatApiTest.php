<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\AppUserConversation;
use App\Models\AppUserConversationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppUserChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_user_can_create_or_load_direct_conversation(): void
    {
        $sender = $this->createAppUser('Sender User', 'senderuser', '01010000001');
        $recipient = $this->createAppUser('Recipient User', 'recipientuser', '01010000002');

        Sanctum::actingAs($sender);

        $this->postJson('/api/app-user/chats/direct', [
            'recipient_app_user_id' => $recipient->id,
        ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.type', 'direct')
            ->assertJsonPath('data.participant.id', $recipient->id);

        $this->assertDatabaseCount('app_user_conversations', 1);
        $this->assertDatabaseCount('app_user_conversation_participants', 2);

        $this->postJson('/api/app-user/chats/direct', [
            'recipient_app_user_id' => $recipient->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseCount('app_user_conversations', 1);
    }

    public function test_chat_index_returns_last_message_and_unread_count(): void
    {
        $sender = $this->createAppUser('Sender User', 'senderchat', '01010000003');
        $recipient = $this->createAppUser('Recipient User', 'recipientchat', '01010000004');

        $conversation = AppUserConversation::query()->create([
            'type' => 'direct',
            'created_by_app_user_id' => $sender->id,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->createMany([
            [
                'app_user_id' => $sender->id,
                'last_read_at' => now()->subMinutes(10),
                'joined_at' => now(),
            ],
            [
                'app_user_id' => $recipient->id,
                'joined_at' => now(),
            ],
        ]);

        AppUserConversationMessage::query()->create([
            'app_user_conversation_id' => $conversation->id,
            'sender_app_user_id' => $recipient->id,
            'body' => 'Hello from recipient',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        AppUserConversationMessage::query()->create([
            'app_user_conversation_id' => $conversation->id,
            'sender_app_user_id' => $recipient->id,
            'body' => 'Another unread message',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        Sanctum::actingAs($sender);

        $this->getJson('/api/app-user/chats')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.0.id', $conversation->id)
            ->assertJsonPath('data.0.participant.id', $recipient->id)
            ->assertJsonPath('data.0.last_message.body', 'Another unread message')
            ->assertJsonPath('data.0.unread_messages_count', 2);
    }

    public function test_app_user_can_send_messages_list_them_and_mark_conversation_as_read(): void
    {
        $sender = $this->createAppUser('Sender User', 'sendermsg', '01010000005');
        $recipient = $this->createAppUser('Recipient User', 'recipientmsg', '01010000006');

        $conversation = AppUserConversation::query()->create([
            'type' => 'direct',
            'created_by_app_user_id' => $sender->id,
        ]);

        $conversation->participants()->createMany([
            [
                'app_user_id' => $sender->id,
                'joined_at' => now(),
            ],
            [
                'app_user_id' => $recipient->id,
                'joined_at' => now(),
            ],
        ]);

        Sanctum::actingAs($sender);

        $this->postJson("/api/app-user/chats/{$conversation->id}/messages", [
            'body' => 'First message',
        ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.body', 'First message')
            ->assertJsonPath('data.is_mine', true);

        Sanctum::actingAs($recipient);

        $this->postJson("/api/app-user/chats/{$conversation->id}/messages", [
            'body' => 'Reply message',
        ])->assertCreated();

        $this->getJson("/api/app-user/chats/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.messages.data.0.body', 'First message')
            ->assertJsonPath('data.messages.data.1.body', 'Reply message');

        $this->patchJson("/api/app-user/chats/{$conversation->id}/read")
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('app_user_conversation_participants', [
            'app_user_conversation_id' => $conversation->id,
            'app_user_id' => $recipient->id,
        ]);
    }

    private function createAppUser(string $name, string $username, string $phone): AppUser
    {
        return AppUser::query()->create([
            'name' => $name,
            'username' => $username,
            'phone' => $phone,
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
