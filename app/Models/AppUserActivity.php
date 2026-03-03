<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_id',
        'type',
        'app_user_post_id',
        'subject_app_user_id',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
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

    public function subjectAppUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'subject_app_user_id');
    }
}
