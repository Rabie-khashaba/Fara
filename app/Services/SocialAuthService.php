<?php

namespace App\Services;

use App\Enums\SocialAuthProvider;
use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAuthService
{
    public function __construct(
        protected SocialiteFactory $socialite,
    ) {
    }

    public function redirectToProvider(string $provider): RedirectResponse
    {
        return $this->driver($provider)->redirect();
    }

    public function authenticate(string $provider): User
    {
        $providerUser = $this->driver($provider)->user();

        return DB::transaction(function () use ($provider, $providerUser): User {
            $socialAccount = UserSocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUser->getId())
                ->first();

            if ($socialAccount) {
                $user = $socialAccount->user;
                $this->ensureDashboardAccess($user);
                $this->syncSocialAccount($socialAccount, $providerUser);
                Auth::login($user, true);

                return $user;
            }

            $user = $this->resolveUser($providerUser);

            if (! $user) {
                throw new AuthenticationException(
                    'No dashboard user matched this social account. Ask an administrator to create or link your account first.'
                );
            }

            $this->ensureDashboardAccess($user);

            $socialAccount = $user->socialAccounts()->create($this->socialAccountPayload($provider, $providerUser));

            $this->syncSocialAccount($socialAccount, $providerUser);

            if (! $user->email && $providerUser->getEmail()) {
                $user->forceFill([
                    'email' => $providerUser->getEmail(),
                    'email_verified_at' => now(),
                ])->save();
            }

            Auth::login($user, true);

            return $user;
        });
    }

    protected function resolveUser(ProviderUser $providerUser): ?User
    {
        $email = $providerUser->getEmail();

        if ($email) {
            $existingUser = User::query()->where('email', $email)->first();

            if ($existingUser) {
                return $existingUser;
            }
        }

        if (! config('social-auth.auto_create_users')) {
            return null;
        }

        if (! $email) {
            throw new AuthenticationException('This provider did not return an email address, so the account cannot be created automatically.');
        }

        $user = User::query()->create([
            'name' => $providerUser->getName() ?: $providerUser->getNickname() ?: 'Social User',
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'type' => config('social-auth.default_user_type', 'admin'),
            'is_active' => (bool) config('social-auth.default_user_active', true),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    protected function syncSocialAccount(UserSocialAccount $socialAccount, ProviderUser $providerUser): void
    {
        $socialAccount->forceFill([
            'provider_email' => $providerUser->getEmail(),
            'provider_avatar' => $providerUser->getAvatar(),
            'access_token' => $providerUser->token,
            'refresh_token' => $providerUser->refreshToken,
            'token_expires_at' => $providerUser->expiresIn ? now()->addSeconds((int) $providerUser->expiresIn) : null,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function socialAccountPayload(string $provider, ProviderUser $providerUser): array
    {
        return [
            'provider' => $provider,
            'provider_user_id' => $providerUser->getId(),
            'provider_email' => $providerUser->getEmail(),
            'provider_avatar' => $providerUser->getAvatar(),
            'access_token' => $providerUser->token,
            'refresh_token' => $providerUser->refreshToken,
            'token_expires_at' => $providerUser->expiresIn ? now()->addSeconds((int) $providerUser->expiresIn) : null,
        ];
    }

    protected function ensureDashboardAccess(User $user): void
    {
        if (! $user->is_active) {
            throw new AuthenticationException('This account is inactive and cannot login.');
        }

        if ($user->type !== 'admin') {
            throw new AuthenticationException('This account does not have access to the dashboard.');
        }
    }

    protected function driver(string $provider): Provider
    {
        $supportedProvider = SocialAuthProvider::tryFrom($provider);

        if (! $supportedProvider) {
            throw new InvalidArgumentException('Unsupported social provider.');
        }

        return $this->socialite->driver($supportedProvider->value)->scopes($supportedProvider->scopes());
    }
}
