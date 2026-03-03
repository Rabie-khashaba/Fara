<?php

namespace App\Services;

use Google_Client;
use RuntimeException;

class GoogleTokenVerifier
{
    /**
     * @return array<string, mixed>
     */
    public function verifyGoogleToken(string $token): array
    {
        $client = new Google_Client([
            'client_id' => config('services.google.client_id'),
        ]);

        $payload = $client->verifyIdToken($token);

        if (! $payload) {
            throw new RuntimeException('Invalid Google token');
        }

        return $payload;
    }
}
