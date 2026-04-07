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
            'image' => $message->image,
            'image_urls' => collect($message->image ?? [])
                ->map(fn ($path) => self::toPublicUrl($path))
                ->filter()
                ->values(),
            'video' => $message->video,
            'video_url' => $message->video
                ? route('app-user.chats.messages.video.show', [
                    'conversationId' => $message->app_user_conversation_id,
                    'messageId' => $message->id,
                ])
                : null,
            'contact' => $message->contact,
            'latitude' => $message->latitude,
            'longitude' => $message->longitude,
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

    private static function toPublicUrl($value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $path = ltrim($value, '/');

        if (str_starts_with($path, 'storage/')) {
            return rtrim(config('app.url'), '/') . '/' . $path;
        }

        return rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;
    }
}