<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserNotification extends Model
{
    protected $fillable = [
        'sender_app_user_id',
        'recipient_app_user_id',
        'target_fcm_token',
        'title',
        'body',
        'data',
        'is_read',
        'read_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'sender_app_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'recipient_app_user_id');
    }
}
