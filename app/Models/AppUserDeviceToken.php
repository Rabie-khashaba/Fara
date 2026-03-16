<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserDeviceToken extends Model
{
    protected $fillable = [
        'app_user_id',
        'token',
        'token_hash',
        'platform',
        'device_name',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public static function makeTokenHash(string $token): string
    {
        return hash('sha256', $token);
    }
}
