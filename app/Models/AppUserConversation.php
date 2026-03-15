<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AppUserConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'created_by_app_user_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'created_by_app_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AppUserConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AppUserConversationMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(AppUserConversationMessage::class)->latestOfMany();
    }
}
