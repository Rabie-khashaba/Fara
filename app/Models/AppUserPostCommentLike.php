<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserPostCommentLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_post_comment_id',
        'app_user_id',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(AppUserPostComment::class, 'app_user_post_comment_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }
}
