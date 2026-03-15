<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_conversation_id',
        'app_user_id',
        'last_read_at',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AppUserConversation::class, 'app_user_conversation_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }
}
