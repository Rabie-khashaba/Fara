<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppUserCheckInCity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'place_name',
        'category',
        'slug',
        'country_code',
        'latitude',
        'longitude',
        'radius_km',
        'is_predefined',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_km' => 'float',
            'is_predefined' => 'boolean',
        ];
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(AppUserCheckIn::class);
    }
}
