<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AppleTokenVerifier
{
    /**
     * @return array<string, mixed>
     */
    public function verifyAppleToken(string $identityToken): array
    {
        $keys = Cache::remember('apple-signing-keys', now()->addHours(6), function (): array {
            $response = Http::get('https://appleid.apple.com/auth/keys');

            if (! $response->successful()) {
                throw new RuntimeException('Unable to fetch Apple public keys');
            }

            $json = $response->json();

            if (! is_array($json)) {
                throw new RuntimeException('Invalid Apple public keys response');
            }

            return $json;
        });

        $publicKeys = JWK::parseKeySet($keys);
        $decoded = JWT::decode($identityToken, $publicKeys);

        return (array) $decoded;
    }
}
