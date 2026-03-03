<?php

namespace App\Enums;

enum SocialAuthProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Apple = 'apple';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return match ($this) {
            self::Google => ['openid', 'profile', 'email'],
            self::Facebook => ['email', 'public_profile'],
            self::Apple => ['name', 'email'],
        };
    }
}
