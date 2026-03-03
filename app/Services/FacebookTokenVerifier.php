<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FacebookTokenVerifier
{
    /**
     * @return array<string, mixed>
     */
    public function verifyFacebookToken(string $token): array
    {
        $response = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture',
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Invalid Facebook token');
        }

        $data = $response->json();

        if (! is_array($data) || empty($data['id'])) {
            throw new RuntimeException('Invalid Facebook token');
        }

        return $data;
    }
}
