<?php

namespace App\Events;

use App\Models\AppUserConversationMessage;

class ChatMessagePayload
{
    public static function fromMessage(AppUserConversationMessage $message): array
    {
        $sender = $message->sender;

        return [
            'id' => $message->id,
            'conversation_id' => $message->app_user_conversation_id,
            'sender' => $sender ? [
                'id' => $sender->id,
                'name' => $sender->name,
                'username' => $sender->username,
                'profile_image_url' => $sender->profile_image_url,
            ] : null,
            'type' => $message->type,
            'body' => $message->body,
            'meta' => $message->meta,
            'sender_app_user_id' => $message->sender_app_user_id,
            'created_at' => $message->created_at?->toISOString(),
            'created_at_label' => $message->created_at?->format('H:i'),
            'edited_at' => $message->edited_at?->toISOString(),
            'deleted_at' => $message->deleted_at?->toISOString(),
            'is_edited' => $message->edited_at !== null,
            'is_deleted' => $message->deleted_at !== null,
        ];
    }
}