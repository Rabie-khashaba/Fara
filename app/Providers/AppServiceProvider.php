<?php

namespace App\Providers;

use Firebase\JWT\JWT;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->configureAppleClientSecret();

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });
    }

    private function configureAppleClientSecret(): void
    {
        if (config('services.apple.client_secret')) {
            return;
        }

        $teamId = config('services.apple.team_id');
        $clientId = config('services.apple.client_id');
        $keyId = config('services.apple.key_id');
        $privateKey = config('services.apple.private_key');

        if (! $teamId || ! $clientId || ! $keyId || ! $privateKey) {
            return;
        }

        $privateKey = str_replace(["\\n", "\r\n"], "\n", $privateKey);
        $now = time();

        $payload = [
            'iss' => $teamId,
            'iat' => $now,
            'exp' => $now + (86400 * 180),
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ];

        $clientSecret = JWT::encode($payload, $privateKey, 'ES256', $keyId);

        config(['services.apple.client_secret' => $clientSecret]);
    }
}
