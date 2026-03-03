<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\AppUserSocialAccount;
use App\Services\AppleTokenVerifier;
use App\Services\FacebookTokenVerifier;
use App\Services\GoogleTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AppUserAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_user_is_not_created_until_register_otp_is_verified(): void
    {
        $response = $this->postJson('/api/app-user/auth/register', [
            'full_name' => 'Test User',
            'username' => 'testuser',
            'phone' => '01000000000',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated();
        $this->assertDatabaseMissing('app_users', ['phone' => '01000000000']);

        $pending = Cache::get('app_user_register:01000000000');
        $this->assertSame(4, strlen((string) ($pending['otp'] ?? '')));

        $verifyResponse = $this->postJson('/api/app-user/auth/verify-register-otp', [
            'phone' => '01000000000',
            'otp' => $pending['otp'] ?? null,
        ]);

        $verifyResponse
            ->assertOk()
            ->assertJsonStructure(['status', 'message', 'data' => ['token', 'user' => ['id', 'full_name', 'username', 'phone']]]);

        $this->assertDatabaseHas('app_users', [
            'phone' => '01000000000',
            'username' => 'testuser',
        ]);
    }

    public function test_app_user_can_login_with_phone_and_password(): void
    {
        AppUser::query()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'phone' => '01000000000',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/app-user/auth/login', [
            'phone' => '01000000000',
            'password' => 'secret123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['status', 'message', 'data' => ['token', 'user' => ['id', 'full_name', 'username', 'phone']]]);
    }

    public function test_app_user_can_login_with_social_token_when_account_is_already_linked(): void
    {
        $appUser = AppUser::query()->create([
            'name' => 'Google User',
            'username' => 'googleuser',
            'phone' => '01000000001',
            'password' => 'secret123',
            'provider' => 'google',
            'provider_id' => 'google-sub-1',
            'is_active' => true,
        ]);

        AppUserSocialAccount::query()->create([
            'app_user_id' => $appUser->id,
            'provider' => 'google',
            'provider_user_id' => 'google-sub-1',
        ]);

        $this->mock(GoogleTokenVerifier::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyGoogleToken')->once()->with('google-token')->andReturn([
                'sub' => 'google-sub-1',
                'name' => 'Google User',
                'email' => 'google@example.com',
            ]);
        });

        $response = $this->postJson('/api/app-user/auth/social-login', [
            'provider' => 'google',
            'token' => 'google-token',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.user.provider', 'google')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'full_name', 'username', 'phone', 'provider']]]);
    }

    public function test_app_user_social_login_starts_registration_when_account_is_not_linked(): void
    {
        $this->mock(GoogleTokenVerifier::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyGoogleToken')->once()->with('google-token')->andReturn([
                'sub' => 'google-sub-2',
                'name' => 'Fresh Google User',
                'email' => 'fresh@example.com',
            ]);
        });

        $response = $this->postJson('/api/app-user/auth/social-login', [
            'provider' => 'google',
            'token' => 'google-token',
            'username' => 'freshgoogle',
            'phone' => '01000000002',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.phone', '01000000002');

        $pending = Cache::get('app_user_register:01000000002');

        $this->assertSame('Fresh Google User', $pending['name'] ?? null);
        $this->assertSame('google', $pending['provider'] ?? null);
        $this->assertSame('google-sub-2', $pending['provider_id'] ?? null);
    }

    public function test_app_user_social_registration_creates_social_account_after_otp_verification(): void
    {
        $this->mock(GoogleTokenVerifier::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyGoogleToken')->once()->with('google-token')->andReturn([
                'sub' => 'google-sub-otp-1',
                'name' => 'OTP Google User',
                'email' => 'otp@example.com',
            ]);
        });

        $this->postJson('/api/app-user/auth/social-login', [
            'provider' => 'google',
            'token' => 'google-token',
            'username' => 'otpgoogle',
            'phone' => '01000000012',
        ])->assertCreated();

        $pending = Cache::get('app_user_register:01000000012');

        $this->postJson('/api/app-user/auth/verify-register-otp', [
            'phone' => '01000000012',
            'otp' => $pending['otp'] ?? null,
        ])->assertOk();

        $this->assertDatabaseHas('app_user_social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-sub-otp-1',
        ]);
    }

    public function test_app_user_google_redirect_endpoint_redirects_to_google(): void
    {
        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('redirectUrl')
            ->once()
            ->with('http://127.0.0.1:8000/api/app-user/auth/login/google/callback')
            ->andReturnSelf();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));

        $this->mock(SocialiteFactory::class, function (Mockery\MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')->once()->with('google')->andReturn($provider);
        });

        $this->get('/api/app-user/auth/login/google/redirect')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_app_user_google_callback_returns_api_token_for_linked_account(): void
    {
        $appUser = AppUser::query()->create([
            'name' => 'Callback User',
            'username' => 'callbackuser',
            'phone' => '01000000003',
            'password' => 'secret123',
            'provider' => 'google',
            'provider_id' => 'google-callback-1',
            'is_active' => true,
        ]);

        AppUserSocialAccount::query()->create([
            'app_user_id' => $appUser->id,
            'provider' => 'google',
            'provider_user_id' => 'google-callback-1',
        ]);

        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('redirectUrl')
            ->once()
            ->with('http://127.0.0.1:8000/api/app-user/auth/login/google/callback')
            ->andReturnSelf();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn(
            $this->makeSocialiteUser('google-callback-1', 'Callback User')
        );

        $this->mock(SocialiteFactory::class, function (Mockery\MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')->once()->with('google')->andReturn($provider);
        });

        $this->get('/api/app-user/auth/login/google/callback')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.user.provider', 'google');

        $this->assertDatabaseHas('app_user_social_accounts', [
            'app_user_id' => $appUser->id,
            'provider' => 'google',
            'provider_user_id' => 'google-callback-1',
            'access_token' => 'social-token',
        ]);
    }

    public function test_app_user_can_login_with_facebook_token_when_account_is_already_linked(): void
    {
        $appUser = AppUser::query()->create([
            'name' => 'Facebook User',
            'username' => 'facebookuser',
            'phone' => '01000000004',
            'password' => 'secret123',
            'provider' => 'facebook',
            'provider_id' => 'facebook-id-1',
            'is_active' => true,
        ]);

        AppUserSocialAccount::query()->create([
            'app_user_id' => $appUser->id,
            'provider' => 'facebook',
            'provider_user_id' => 'facebook-id-1',
        ]);

        $this->mock(FacebookTokenVerifier::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyFacebookToken')->once()->with('facebook-token')->andReturn([
                'id' => 'facebook-id-1',
                'name' => 'Facebook User',
                'email' => 'facebook@example.com',
            ]);
        });

        $this->postJson('/api/app-user/auth/social-login', [
            'provider' => 'facebook',
            'token' => 'facebook-token',
        ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.user.provider', 'facebook');
    }

    public function test_app_user_can_login_with_apple_identity_token_when_account_is_already_linked(): void
    {
        $appUser = AppUser::query()->create([
            'name' => 'Apple User',
            'username' => 'appleuser',
            'phone' => '01000000005',
            'password' => 'secret123',
            'provider' => 'apple',
            'provider_id' => 'apple-sub-1',
            'is_active' => true,
        ]);

        AppUserSocialAccount::query()->create([
            'app_user_id' => $appUser->id,
            'provider' => 'apple',
            'provider_user_id' => 'apple-sub-1',
        ]);

        $this->mock(AppleTokenVerifier::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyAppleToken')->once()->with('apple-identity-token')->andReturn([
                'sub' => 'apple-sub-1',
                'email' => 'apple@example.com',
            ]);
        });

        $this->postJson('/api/app-user/auth/social-login', [
            'provider' => 'apple',
            'token' => 'apple-identity-token',
        ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.user.provider', 'apple');
    }

    protected function makeSocialiteUser(string $id, string $name): SocialiteUser
    {
        return (new SocialiteUser())
            ->setRaw(['sub' => $id, 'name' => $name])
            ->map([
                'id' => $id,
                'name' => $name,
            ])
            ->setToken('social-token');
    }
}
