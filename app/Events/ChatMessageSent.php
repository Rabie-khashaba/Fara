<?php

namespace App\Events;

use App\Models\AppUserConversationMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
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
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;

        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->app_user_conversation_id,
                'sender' => $sender ? [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'username' => $sender->username,
                    'profile_image_url' => $sender->profile_image_url,
                ] : null,
                'type' => $this->message->type,
                'body' => $this->message->body,
                'meta' => $this->message->meta,
                'sender_app_user_id' => $this->message->sender_app_user_id,
                'created_at' => $this->message->created_at?->toISOString(),
                'created_at_label' => $this->message->created_at?->format('H:i'),
            ],
        ];
    }
}
