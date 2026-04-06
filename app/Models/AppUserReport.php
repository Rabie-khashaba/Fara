<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserReport extends Model
{
    use HasFactory;

    public const TYPE_INAPPROPRIATE_CONTENT = 'inappropriate_content';
    public const TYPE_HARASSMENT = 'harassment';
    public const TYPE_HATE_SPEECH = 'hate_speech';
    public const TYPE_VIOLENCE = 'violence';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_INAPPROPRIATE_CONTENT,
        self::TYPE_HARASSMENT,
        self::TYPE_HATE_SPEECH,
        self::TYPE_VIOLENCE,
        self::TYPE_OTHER,
    ];

    protected $fillable = [
        'reporter_app_user_id',
        'reported_app_user_id',
        'report_type',
        'details',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'reporter_app_user_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'reported_app_user_id');
    }
}
