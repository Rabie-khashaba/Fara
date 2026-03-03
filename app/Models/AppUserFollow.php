<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserFollow extends Model
{
    use HasFactory;

    protected $fillable = [
        'follower_app_user_id',
        'following_app_user_id',
    ];

    public function follower(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'follower_app_user_id');
    }

    public function following(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'following_app_user_id');
    }
}
