<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserCheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_id',
        'app_user_post_id',
        'app_user_check_in_city_id',
        'place_name',
        'category',
        'latitude',
        'longitude',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'checked_in_at' => 'datetime',
        ];
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(AppUserPost::class, 'app_user_post_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(AppUserCheckInCity::class, 'app_user_check_in_city_id');
    }
}
