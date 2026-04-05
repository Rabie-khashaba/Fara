<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
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
        $payload = (array) $decoded;

        $this->assertApplePayloadIsValid($payload);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function assertApplePayloadIsValid(array $payload): void
    {
        $issuer = (string) config('services.apple.issuer', 'https://appleid.apple.com');
        $allowedAudiences = Arr::wrap(config('services.apple.allowed_client_ids', []));
        $allowedAudiences = array_values(array_filter(array_map('strval', $allowedAudiences)));

        if (($payload['iss'] ?? null) !== $issuer) {
            throw new RuntimeException('Invalid Apple token issuer');
        }

        if ($allowedAudiences === []) {
            throw new RuntimeException('Apple client ID is not configured');
        }

        if (! in_array((string) ($payload['aud'] ?? ''), $allowedAudiences, true)) {
            throw new RuntimeException('Invalid Apple token audience');
        }

        if (empty($payload['sub'])) {
            throw new RuntimeException('Invalid Apple token subject');
        }
    }
}
