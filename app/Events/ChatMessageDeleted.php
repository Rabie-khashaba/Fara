<?php

namespace App\Events;

use App\Models\AppUserConversationMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public AppUserConversationMessage $message
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.conversation.'.$this->message->app_user_conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => ChatMessagePayload::fromMessage($this->message),
            'is_new_message' => false,
            'sync_chat_list' => false,
            'sync_unread_count' => false,
        ];
    }
}