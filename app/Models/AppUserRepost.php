<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserRepost extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_id',
        'app_user_post_id',
    ];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(AppUserPost::class, 'app_user_post_id');
    }
}
