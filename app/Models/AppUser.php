<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $appends = [
        'profile_image_url',
        'cover_photo_url',
    ];

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
        'fcm_token',
        'profile_image',
        'cover_photo',
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

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'app_user_package')
            ->withTimestamps();
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

    public function sharedPosts(): HasMany
    {
        return $this->hasMany(AppUserSharedPost::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AppUserActivity::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(AppUserCheckIn::class);
    }

    public function sentNotifications(): HasMany
    {
        return $this->hasMany(AppUserNotification::class, 'sender_app_user_id');
    }

    public function receivedNotifications(): HasMany
    {
        return $this->hasMany(AppUserNotification::class, 'recipient_app_user_id');
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

    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->profile_image);
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->cover_photo);
    }

    private function toPublicUrl($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(function ($item) {
                return $this->toPublicUrl($item);
            }, $value);
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $path = ltrim($value, '/');

        if (str_starts_with($path, 'storage/')) {
            return rtrim(config('app.url'), '/') . '/' . $path;
        }

        return rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;
    }
}
