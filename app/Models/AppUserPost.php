<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppUserPost extends Model
{
    use HasFactory;

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'app_user_id',
        'content',
        'image',
        'location',
        'status',
        'published_at',
        'is_hide',
        'reposted_post_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_hide' => 'boolean',
        ];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hide', false);
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(AppUserPostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AppUserPostComment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AppUserActivity::class);
    }

    public function repostedPost(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reposted_post_id');
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(AppUserRepost::class, 'app_user_post_id');
    }

    public function sharedPosts(): HasMany
    {
        return $this->hasMany(AppUserSharedPost::class, 'app_user_post_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->image);
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
