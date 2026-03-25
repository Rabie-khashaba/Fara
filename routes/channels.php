<?php

use App\Models\AppUserConversationParticipant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.conversation.{conversationId}', function ($user, int $conversationId) {
    return AppUserConversationParticipant::query()
        ->where('app_user_conversation_id', $conversationId)
        ->where('app_user_id', $user->id)
        ->exists();
});