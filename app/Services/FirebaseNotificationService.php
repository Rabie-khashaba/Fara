<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FirebaseNotificationService
{
    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        $endpoint = $this->getFcmEndpoint();
        $accessToken = $this->getAccessToken();

        $message = [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        $normalizedData = $this->normalizeDataPayload($data);
        if ($normalizedData !== []) {
            $message['data'] = $normalizedData;
        }

        $payload = [
            'message' => $message,
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($endpoint, $payload);

        if ($response->failed()) {
            return [
                'status' => false,
                'message' => 'Firebase notification request failed.',
                'error' => $response->json() ?: $response->body(),
            ];
        }

        return [
            'status' => true,
            'message' => 'Notification sent successfully.',
            'data' => $response->json(),
        ];
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($tokens as $token) {
            $result = $this->sendToToken((string) $token, $title, $body, $data);
            $results[] = [
                'token' => $token,
                'status' => $result['status'],
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
                'data' => $result['data'] ?? null,
            ];

            if ($result['status']) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        return [
            'status' => $failedCount === 0,
            'message' => $failedCount === 0
                ? 'All notifications sent successfully.'
                : 'Some notifications failed.',
            'data' => [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'results' => $results,
            ],
        ];
    }

    private function getFcmEndpoint(): string
    {
        $projectId = (string) config('services.firebase.project_id');
        if ($projectId === '') {
            throw new \RuntimeException('Firebase project id is not configured.');
        }

        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    private function getAccessToken(): string
    {
        $credentials = $this->firebaseCredentials();
        $cacheKey = 'firebase_fcm_access_token_' . md5($credentials['client_email']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials) {
            $clientEmail = $credentials['client_email'];
            $privateKey = $credentials['private_key'];
            $tokenUri = $credentials['token_uri'];

            $now = time();
            $jwtHeader = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));
            $jwtPayload = $this->base64UrlEncode(json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
                'jti' => (string) Str::uuid(),
            ]));

            $signatureInput = "{$jwtHeader}.{$jwtPayload}";
            $signature = '';
            $privateKeyResource = openssl_pkey_get_private($privateKey);
            if ($privateKeyResource === false) {
                throw new \RuntimeException('Invalid Firebase private key.');
            }

            $signed = openssl_sign($signatureInput, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
            openssl_free_key($privateKeyResource);

            if (!$signed) {
                throw new \RuntimeException('Unable to sign Firebase JWT.');
            }

            $jwt = $signatureInput . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed() || !$response->json('access_token')) {
                throw new \RuntimeException('Unable to get Firebase access token.');
            }

            return (string) $response->json('access_token');
        });
    }

    private function firebaseCredentials(): array
    {
        $clientEmail = (string) config('services.firebase.client_email');
        $privateKey = (string) config('services.firebase.private_key');
        $tokenUri = (string) config('services.firebase.token_uri');

        if ($clientEmail === '' || $privateKey === '' || $tokenUri === '') {
            throw new \RuntimeException('Firebase credentials are not fully configured.');
        }

        return [
            'client_email' => $clientEmail,
            'private_key' => str_replace('\n', "\n", $privateKey),
            'token_uri' => $tokenUri,
        ];
    }

    private function normalizeDataPayload(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[(string) $key] = is_scalar($value) || $value === null
                ? (string) ($value ?? '')
                : json_encode($value);
        }

        return $normalized;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
