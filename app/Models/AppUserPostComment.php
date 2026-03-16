<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppUserPostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_post_id',
        'app_user_id',
        'comment',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(AppUserPost::class, 'app_user_post_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(AppUserPostCommentLike::class, 'app_user_post_comment_id');
    }
}
