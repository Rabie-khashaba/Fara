<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserConversationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_conversation_id',
        'sender_app_user_id',
        'type',
        'body',
        'meta',
        'edited_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AppUserConversation::class, 'app_user_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'sender_app_user_id');
    }
}
