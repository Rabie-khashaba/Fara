<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'phone',
        'password',
        'otp',
        'expired_otp_at',
        'provider',
        'provider_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'otp',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'expired_otp_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(AppUserSocialAccount::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(AppUserPost::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(AppUserFollow::class, 'following_app_user_id');
    }

    public function following(): HasMany
    {
        return $this->hasMany(AppUserFollow::class, 'follower_app_user_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(AppUserPostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AppUserPostComment::class);
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(AppUserRepost::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AppUserActivity::class);
    }

    public function likedPosts(): HasManyThrough
    {
        return $this->hasManyThrough(
            AppUserPost::class,
            AppUserPostLike::class,
            'app_user_id',
            'id',
            'id',
            'app_user_post_id'
        );
    }
}
