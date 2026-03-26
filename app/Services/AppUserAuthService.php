<?php

namespace App\Services;

use App\Enums\SocialAuthProvider;
use App\Models\AppUser;
use App\Models\AppUserDeviceToken;
use App\Models\AppUserSocialAccount;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Laravel\Socialite\Contracts\User as ProviderUser;

class AppUserAuthService
{
    public function __construct(
        protected WhatsAppService $whatsAppService,
        protected SocialiteFactory $socialite,
        protected GoogleTokenVerifier $googleTokenVerifier,
        protected FacebookTokenVerifier $facebookTokenVerifier,
        protected AppleTokenVerifier $appleTokenVerifier,
    ) {
    }

    private const OTP_TTL_MINUTES = 5;

    public function requestRegistration(array $data): array
    {
        $data['phone'] = $this->normalizePhone($data['phone']);

        if ($this->findAppUserByPhone($data['phone'])) {
            return ['error' => 'Phone number already registered'];
        }

        if (AppUser::query()->where('username', $data['username'])->exists()) {
            return ['error' => 'Username already registered'];
        }

        $pendingPhone = $this->getPendingRegistrationByPhone($data['phone']);

        if ($pendingPhone && ($pendingPhone['username'] ?? null) !== $data['username']) {
            return ['error' => 'A pending registration already exists for this phone number'];
        }

        $pendingUsernamePhone = Cache::get($this->registrationUsernameKey($data['username']));

        if ($pendingUsernamePhone && $pendingUsernamePhone !== $data['phone']) {
            return ['error' => 'This username is already reserved by a pending registration'];
        }

        $otp = $this->generateOtp();
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        $this->storePendingRegistration($data['phone'], [
            'name' => $data['full_name'],
            'username' => $data['username'],
            'phone' => $data['phone'],
            'password' => isset($data['password']) ? Hash::make($data['password']) : null,
            'fcm_token' => $data['fcm_token'] ?? null,
            'platform' => $data['platform'] ?? null,
            'device_name' => $data['device_name'] ?? null,
            'provider' => $data['provider'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'otp' => $otp,
            'otp_expires_at' => $expiresAt->toISOString(),
        ], $expiresAt);

        $sendResult = $this->sendOtpMessage($data['phone'], $otp, 'Your registration verification code is');

        if (empty($sendResult['success'])) {
            $this->forgetPendingRegistration($data['phone'], $data['username']);

            return ['error' => $sendResult['error'] ?? 'Failed to send OTP'];
        }

        return [
            'message' => 'OTP sent successfully',
            'phone' => $data['phone'],
            'otp_expires_at' => $expiresAt->toISOString(),
        ];
    }

    public function completeRegistration(string $phone, string $otp): array
    {
        $phone = $this->normalizePhone($phone);
        $pendingRegistration = $this->getPendingRegistrationByPhone($phone);

        if (
            ! $pendingRegistration
            || ($pendingRegistration['otp'] ?? null) !== $otp
            || now()->isAfter(Carbon::parse($pendingRegistration['otp_expires_at'] ?? now()->subSecond()))
        ) {
            return ['error' => 'Invalid or expired OTP'];
        }

        $appUser = AppUser::create([
            'name' => $pendingRegistration['name'],
            'username' => $pendingRegistration['username'],
            'phone' => $pendingRegistration['phone'],
            'password' => $pendingRegistration['password'] ?: Str::password(32),
            'provider' => $pendingRegistration['provider'],
            'provider_id' => $pendingRegistration['provider_id'],
            'otp' => null,
            'expired_otp_at' => null,
            'is_active' => true,
        ]);

        if (! empty($pendingRegistration['fcm_token'])) {
            $this->storeDeviceToken(
                $appUser,
                (string) $pendingRegistration['fcm_token'],
                $pendingRegistration['platform'] ?? null,
                $pendingRegistration['device_name'] ?? null
            );
        }

        $token = $appUser->createToken('app-user-token')->plainTextToken;

        $this->forgetPendingRegistration($pendingRegistration['phone'], $pendingRegistration['username']);

        return [
            'message' => 'Registration completed successfully',
            'token' => $token,
            'user' => $this->userPayload($appUser),
        ];
    }

    public function resendRegisterOtp(string $phone): array
    {
        $phone = $this->normalizePhone($phone);
        $appUser = $this->findAppUserByPhone($phone);

        if ($appUser) {
            $otp = $this->generateOtp();
            $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);
            $previousOtp = $appUser->otp;
            $previousOtpExpiry = $appUser->expired_otp_at;

            $appUser->forceFill([
                'otp' => $otp,
                'expired_otp_at' => $expiresAt,
            ])->save();

            $sendResult = $this->sendOtpMessage($phone, $otp, 'Your verification code is');

            if (empty($sendResult['success'])) {
                $appUser->forceFill([
                    'otp' => $previousOtp,
                    'expired_otp_at' => $previousOtpExpiry,
                ])->save();

                return ['error' => $sendResult['error'] ?? 'Failed to send OTP'];
            }

            return [
                'message' => 'OTP sent successfully.',
                'phone' => $appUser->phone,
                'otp_expires_at' => $expiresAt->toISOString(),
            ];
        }

        $pendingRegistration = $this->getPendingRegistrationByPhone($phone);

        if (! $pendingRegistration) {
            return ['error' => 'User not found or registration expired.'];
        }

        $otp = $this->generateOtp();
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);
        $previousPendingRegistration = $pendingRegistration;

        $pendingRegistration['otp'] = $otp;
        $pendingRegistration['otp_expires_at'] = $expiresAt->toISOString();

        $this->storePendingRegistration($phone, $pendingRegistration, $expiresAt);

        $sendResult = $this->sendOtpMessage($phone, $otp, 'Your registration verification code is');

        if (empty($sendResult['success'])) {
            $previousExpiry = Carbon::parse(
                $previousPendingRegistration['otp_expires_at'] ?? now()->addMinutes(self::OTP_TTL_MINUTES)
            );
            $this->storePendingRegistration($phone, $previousPendingRegistration, $previousExpiry);

            return ['error' => $sendResult['error'] ?? 'Failed to send OTP'];
        }

        return [
            'message' => 'OTP sent successfully.',
            'phone' => $phone,
            'registration_pending' => true,
            'otp_expires_at' => $expiresAt->toISOString(),
        ];
    }

    public function loginByPhone(
        string $phone,
        string $password,
        ?string $fcmToken = null,
        ?string $platform = null,
        ?string $deviceName = null
    ): array
    {
        $appUser = $this->findAppUserByPhone($this->normalizePhone($phone));

        if (! $appUser) {
            return ['error' => 'User not found'];
        }

        if (! Hash::check($password, $appUser->password)) {
            return ['error' => 'Invalid credentials'];
        }

        if (! $appUser->is_active) {
            return ['error' => 'This account is blocked', 'code' => 403];
        }

        if ($fcmToken !== null) {
            $this->storeDeviceToken($appUser, $fcmToken, $platform, $deviceName);
        }

        $token = $appUser->createToken('app-user-token')->plainTextToken;

        return [
            'user' => $this->userPayload($appUser),
            'token' => $token,
        ];
    }

    public function socialLogin(array $data): array
    {
        $providerUser = $this->resolveProviderUser($data['provider'], $data['token']);
        $appUser = DB::transaction(function () use ($data, $providerUser): AppUser {
            $socialAccount = $this->findSocialAccount($data['provider'], (string) $providerUser->getId());

            if ($socialAccount) {
                $appUser = $socialAccount->appUser;
                $this->syncSocialAccount($socialAccount, $providerUser);

                return $appUser;
            }

            $appUser = $this->createSocialAppUser($data, $providerUser);

            $socialAccount = $appUser->socialAccounts()->create(
                $this->socialAccountPayload($data['provider'], $providerUser)
            );

            $this->syncSocialAccount($socialAccount, $providerUser);

            return $appUser;
        });

        if (! $appUser->is_active) {
            return ['error' => 'This account is blocked', 'code' => 403];
        }

        $token = $appUser->createToken('app-user-token')->plainTextToken;

        return [
            'message' => 'Social login successful',
            'token' => $token,
            'user' => $this->userPayload($appUser),
        ];
    }

    public function redirectToSocialProvider(string $provider): RedirectResponse
    {
        return $this->socialDriver($provider)->redirect();
    }

    public function socialLoginByCallback(string $provider): array
    {
        $providerUser = $this->socialDriver($provider)->user();
        $appUser = DB::transaction(function () use ($provider, $providerUser): AppUser {
            $socialAccount = $this->findSocialAccount($provider, (string) $providerUser->getId());

            if ($socialAccount) {
                $appUser = $socialAccount->appUser;
                $this->syncSocialAccount($socialAccount, $providerUser);

                return $appUser;
            }

            $appUser = $this->createSocialAppUser(['provider' => $provider], $providerUser);
            $socialAccount = $appUser->socialAccounts()->create(
                $this->socialAccountPayload($provider, $providerUser)
            );

            $this->syncSocialAccount($socialAccount, $providerUser);

            return $appUser;
        });

        if (! $appUser->is_active) {
            return ['error' => 'This account is blocked', 'code' => 403];
        }

        $token = $appUser->createToken('app-user-token')->plainTextToken;

        return [
            'message' => 'Social login successful',
            'token' => $token,
            'user' => $this->userPayload($appUser),
        ];
    }

    public function forgotPassword(string $phone): array
    {
        $appUser = $this->findAppUserByPhone($this->normalizePhone($phone));

        if (! $appUser) {
            return ['error' => 'User not found'];
        }

        $otp = $this->generateOtp();
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        $appUser->update([
            'otp' => $otp,
            'expired_otp_at' => $expiresAt,
        ]);

        $sendResult = $this->sendOtpMessage($appUser->phone, $otp, 'Your password reset code is');

        if (empty($sendResult['success'])) {
            $appUser->update([
                'otp' => null,
                'expired_otp_at' => null,
            ]);

            return ['error' => $sendResult['error'] ?? 'Failed to send OTP'];
        }

        return [
            'message' => 'OTP sent successfully',
            'phone' => $appUser->phone,
            'otp_expires_at' => $expiresAt->toISOString(),
        ];
    }

    public function verifyForgotPasswordOtp(string $phone, string $otp): array
    {
        $appUser = $this->findAppUserByPhone($this->normalizePhone($phone));

        if (! $appUser || $appUser->otp !== $otp || $appUser->expired_otp_at?->isPast()) {
            return ['error' => 'Invalid or expired OTP'];
        }



        $appUser->forceFill([
            'otp' => null,
            'expired_otp_at' => null,
        ])->save();

        return [
            'message' => 'OTP verified successfully'
        ];
    }

    public function resetPassword(string $phone, string $password): array
    {
        $appUser = $this->findAppUserByPhone($this->normalizePhone($phone));

        if (! $appUser) {
            return ['error' => 'User not found'];
        }

        $appUser->forceFill([
            'password' => $password,
            'otp' => null,
            'expired_otp_at' => null,
        ])->save();

        return [
            'message' => 'Password reset successfully',
        ];
    }

    public function logout(?AppUser $user): array
    {
        if (! $user) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        } else {
            $user->tokens()->delete();
        }

        return [
            'message' => 'Logged out successfully',
        ];
    }

    public function registrationCacheKey(string $phone): string
    {
        return 'app_user_register:' . $phone;
    }

    private function getPendingRegistrationByPhone(string $phone): ?array
    {
        return Cache::get($this->registrationCacheKey($phone));
    }

    private function storePendingRegistration(string $phone, array $payload, \DateTimeInterface $expiresAt): void
    {
        $existing = $this->getPendingRegistrationByPhone($phone);

        if ($existing && isset($existing['username']) && $existing['username'] !== $payload['username']) {
            Cache::forget($this->registrationUsernameKey($existing['username']));
        }

        Cache::put($this->registrationCacheKey($phone), $payload, $expiresAt);
        Cache::put($this->registrationUsernameKey($payload['username']), $phone, $expiresAt);
    }

    private function forgetPendingRegistration(string $phone, string $username): void
    {
        Cache::forget($this->registrationCacheKey($phone));
        Cache::forget($this->registrationUsernameKey($username));
    }

    private function registrationUsernameKey(string $username): string
    {
        return 'app_user_register_username:' . Str::lower($username);
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function sendOtpMessage(string $phone, string $otp, string $prefix): array
    {
        return $this->whatsAppService->send($phone, "{$prefix}: {$otp}");
    }

    private function userPayload(AppUser $appUser): array
    {
        return [
            'id' => $appUser->id,
            'full_name' => $appUser->name,
            'username' => $appUser->username,
            'email' => $appUser->email,
            'phone' => $appUser->phone,
            'provider' => $appUser->provider,
            'is_active' => $appUser->is_active,
        ];
    }

    private function storeDeviceToken(
        AppUser $appUser,
        string $token,
        ?string $platform = null,
        ?string $deviceName = null
    ): void {
        AppUserDeviceToken::query()->updateOrCreate(
            ['token_hash' => AppUserDeviceToken::makeTokenHash($token)],
            [
                'app_user_id' => $appUser->id,
                'token' => $token,
                'platform' => $platform,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ]
        );
    }

    protected function resolveProviderUser(string $provider, string $token): ProviderUser
    {
        if ($provider === SocialAuthProvider::Google->value) {
            return $this->mapGooglePayloadToProviderUser(
                $this->googleTokenVerifier->verifyGoogleToken($token),
                $token
            );
        }

        if ($provider === SocialAuthProvider::Facebook->value) {
            return $this->mapFacebookPayloadToProviderUser(
                $this->facebookTokenVerifier->verifyFacebookToken($token),
                $token
            );
        }

        if ($provider === SocialAuthProvider::Apple->value) {
            return $this->mapApplePayloadToProviderUser(
                $this->appleTokenVerifier->verifyAppleToken($token),
                $token
            );
        }

        $driver = $this->socialDriver($provider);

        return $driver->userFromToken($token);
    }

    protected function socialDriver(string $provider): AbstractProvider
    {
        /** @var AbstractProvider $driver */
        $driver = $this->socialite->driver(SocialAuthProvider::from($provider)->value);

        return $driver->stateless();
    }

    protected function findByProviderIdentity(string $provider, string $providerId): ?AppUser
    {
        return $this->findSocialAccount($provider, $providerId)?->appUser;
    }

    protected function findSocialAccount(string $provider, string $providerId): ?AppUserSocialAccount
    {
        return AppUserSocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerId)
            ->first();
    }

    protected function looksLikeJwt(string $token): bool
    {
        return substr_count($token, '.') === 2;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function mapGooglePayloadToProviderUser(array $payload, string $token): ProviderUser
    {
        return (new SocialiteUser())
            ->setRaw($payload)
            ->map([
                'id' => (string) ($payload['sub'] ?? ''),
                'name' => $payload['name'] ?? null,
                'email' => $payload['email'] ?? null,
                'avatar' => $payload['picture'] ?? null,
            ])
            ->setToken($token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function mapFacebookPayloadToProviderUser(array $payload, string $token): ProviderUser
    {
        return (new SocialiteUser())
            ->setRaw($payload)
            ->map([
                'id' => (string) ($payload['id'] ?? ''),
                'name' => $payload['name'] ?? null,
                'email' => $payload['email'] ?? null,
                'avatar' => data_get($payload, 'picture.data.url'),
            ])
            ->setToken($token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function mapApplePayloadToProviderUser(array $payload, string $token): ProviderUser
    {
        return (new SocialiteUser())
            ->setRaw($payload)
            ->map([
                'id' => (string) ($payload['sub'] ?? ''),
                'name' => $payload['name'] ?? null,
                'email' => $payload['email'] ?? null,
                'avatar' => null,
            ])
            ->setToken($token);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createSocialAppUser(array $data, ProviderUser $providerUser): AppUser
    {
        $provider = (string) $data['provider'];
        $providerId = (string) $providerUser->getId();
        $email = $providerUser->getEmail();

        return AppUser::query()->create([
            'name' => $data['full_name'] ?? $providerUser->getName() ?? $providerUser->getNickname() ?? 'Social User',
            'username' => $this->resolveSocialUsername($data, $providerUser, $provider, $providerId),
            'email' => $this->resolveSocialEmail($email, $provider, $providerId),
            'email_verified_at' => $email ? now() : null,
            // 'phone' => $this->resolveSocialPhone($data, $provider, $providerId),
            'password' => Str::password(32),
            'provider' => $provider,
            'provider_id' => $providerId,
            'otp' => null,
            'expired_otp_at' => null,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSocialUsername(array $data, ProviderUser $providerUser, string $provider, string $providerId): string
    {
        $candidate = $data['username'] ?? $providerUser->getNickname();

        if (! $candidate && $providerUser->getEmail()) {
            $candidate = Str::before((string) $providerUser->getEmail(), '@');
        }

        if (! $candidate) {
            $candidate = Str::slug($providerUser->getName() ?? '', '_');
        }

        $candidate = Str::lower((string) $candidate);
        $candidate = preg_replace('/[^a-z0-9_]/', '_', $candidate) ?: '';
        $candidate = trim($candidate, '_');

        if ($candidate === '') {
            $candidate = $provider . '_user';
        }

        $candidate = Str::limit($candidate, 40, '');
        $username = $candidate;
        $suffix = 1;

        while (AppUser::query()->where('username', $username)->exists()) {
            $tail = '_' . $suffix;
            $username = Str::limit($candidate, 40 - strlen($tail), '') . $tail;
            $suffix++;
        }

        return $username !== '' ? $username : $provider . '_' . substr($providerId, -8);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSocialPhone(array $data, string $provider, string $providerId): string
    {
        $phone = $this->normalizePhone((string) ($data['phone'] ?? ''));

        if ($phone !== '') {
            $existingUser = $this->findAppUserByPhone($phone);

            if ($existingUser && ! $this->findByProviderIdentity($provider, $providerId)?->is($existingUser)) {
                return $this->generatePlaceholderPhone($provider, $providerId);
            }

            return $phone;
        }

        return $this->generatePlaceholderPhone($provider, $providerId);
    }

    protected function findAppUserByPhone(string $phone): ?AppUser
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $digitsOnlyPhone = $this->digitsOnlyPhone($normalizedPhone);

        return AppUser::query()
            ->where('phone', $normalizedPhone)
            ->orWhereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') = ?",
                [$digitsOnlyPhone]
            )
            ->first();
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return $phone;
        }

        $arabicIndic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $easternArabicIndic = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $phone = str_replace($arabicIndic, $western, $phone);
        $phone = str_replace($easternArabicIndic, $western, $phone);
        $phone = preg_replace('/\s+/', '', $phone) ?? $phone;

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (str_starts_with($phone, '20') && ! str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    protected function digitsOnlyPhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    protected function resolveSocialEmail(?string $email, string $provider, string $providerId): ?string
    {
        $email = $email ? trim($email) : null;

        if (! $email) {
            return null;
        }

        $existingUser = AppUser::query()->where('email', $email)->first();

        if (! $existingUser) {
            return $email;
        }

        if ($this->findByProviderIdentity($provider, $providerId)?->is($existingUser)) {
            return $email;
        }

        return null;
    }

    protected function generatePlaceholderPhone(string $provider, string $providerId): string
    {
        $base = 'social-' . $provider . '-' . substr(sha1($providerId), 0, 18);
        $phone = $base;
        $suffix = 1;

        while (AppUser::query()->where('phone', $phone)->exists()) {
            $tail = '-' . $suffix;
            $phone = Str::limit($base, 30 - strlen($tail), '') . $tail;
            $suffix++;
        }

        return $phone;
    }

    protected function syncSocialAccount(AppUserSocialAccount $socialAccount, ProviderUser $providerUser): void
    {
        $socialAccount->forceFill([
            'provider_email' => $providerUser->getEmail(),
            'provider_avatar' => $providerUser->getAvatar(),
            'access_token' => $providerUser->token,
            'refresh_token' => $providerUser->refreshToken,
            'token_expires_at' => $providerUser->expiresIn ? now()->addSeconds((int) $providerUser->expiresIn) : null,
        ])->save();

        $appUser = $socialAccount->appUser;

        if (! $appUser->email && $providerUser->getEmail()) {
            $appUser->forceFill([
                'email' => $providerUser->getEmail(),
                'email_verified_at' => now(),
            ])->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function socialAccountPayload(string $provider, ProviderUser $providerUser): array
    {
        return [
            'provider' => $provider,
            'provider_user_id' => (string) $providerUser->getId(),
            'provider_email' => $providerUser->getEmail(),
            'provider_avatar' => $providerUser->getAvatar(),
            'access_token' => $providerUser->token,
            'refresh_token' => $providerUser->refreshToken,
            'token_expires_at' => $providerUser->expiresIn ? now()->addSeconds((int) $providerUser->expiresIn) : null,
        ];
    }
}