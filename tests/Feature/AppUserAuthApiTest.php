<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\AppUserSocialAccount;
use App\Services\AppleTokenVerifier;
use App\Services\FacebookTokenVerifier;
use App\Services\GoogleTokenVerifier;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
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
        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andReturn([
                'success' => true,
                'data' => [],
            ]);
        });

        $response = $this->postJson('/api/app-user/auth/register', [
            'full_name' => 'Test User',
            'username' => 'testuser',
            'phone' => '01000000000',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'fcm_token' => 'register-fcm-token',
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
            'fcm_token' => 'register-fcm-token',
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

    public function test_app_user_login_returns_reports_inactive_reason_when_user_was_blocked_by_reports(): void
    {
        AppUser::query()->create([
            'name' => 'Blocked User',
            'username' => 'blockedreports',
            'phone' => '01000000100',
            'password' => 'secret123',
            'is_active' => false,
            'inactive_reason' => AppUser::INACTIVE_REASON_REPORTS,
        ]);

        $this->postJson('/api/app-user/auth/login', [
            'phone' => '01000000100',
            'password' => 'secret123',
        ])
            ->assertForbidden()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'This account is inactive because it was blocked due to reports.')
            ->assertJsonPath('data.inactive_reason', AppUser::INACTIVE_REASON_REPORTS);
    }

    public function test_app_user_login_returns_admin_inactive_reason_when_user_was_deactivated_by_admin(): void
    {
        AppUser::query()->create([
            'name' => 'Inactive User',
            'username' => 'blockedadmin',
            'phone' => '01000000101',
            'password' => 'secret123',
            'is_active' => false,
            'inactive_reason' => AppUser::INACTIVE_REASON_ADMIN,
        ]);

        $this->postJson('/api/app-user/auth/login', [
            'phone' => '01000000101',
            'password' => 'secret123',
        ])
            ->assertForbidden()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'This account is inactive because it was deactivated by admin.')
            ->assertJsonPath('data.inactive_reason', AppUser::INACTIVE_REASON_ADMIN);
    }

    public function test_app_user_login_can_store_fcm_token_when_provided(): void
    {
        $appUser = AppUser::query()->create([
            'name' => 'Test User',
            'username' => 'testuserfcm',
            'phone' => '01000000013',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $this->postJson('/api/app-user/auth/login', [
            'phone' => '01000000013',
            'password' => 'secret123',
            'fcm_token' => 'sample-fcm-token',
        ])
            ->assertOk()
            ->assertJsonStructure(['status', 'message', 'data' => ['token', 'user' => ['id', 'full_name', 'username', 'phone']]]);

        $this->assertDatabaseHas('app_users', [
            'id' => $appUser->id,
            'fcm_token' => 'sample-fcm-token',
        ]);
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

    public function test_app_user_can_delete_own_profile(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('app-user-profiles/profile.jpg', 'profile');
        Storage::disk('public')->put('app-user-profiles/cover.jpg', 'cover');

        $appUser = AppUser::query()->create([
            'name' => 'Delete Me',
            'username' => 'deleteme',
            'phone' => '01000000006',
            'password' => 'secret123',
            'is_active' => true,
            'profile_image' => 'app-user-profiles/profile.jpg',
            'cover_photo' => 'app-user-profiles/cover.jpg',
        ]);

        Sanctum::actingAs($appUser);
        $appUser->createToken('app-user-token');

        $this->deleteJson('/api/app-user/profile/'.$appUser->id)
            ->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'Profile deleted successfully',
            ]);

        $this->assertDatabaseMissing('app_users', ['id' => $appUser->id]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => AppUser::class,
            'tokenable_id' => $appUser->id,
        ]);
        Storage::disk('public')->assertMissing('app-user-profiles/profile.jpg');
        Storage::disk('public')->assertMissing('app-user-profiles/cover.jpg');
    }

    public function test_app_user_cannot_delete_another_profile_by_id(): void
    {
        $authenticatedUser = AppUser::query()->create([
            'name' => 'Authenticated User',
            'username' => 'authuser',
            'phone' => '01000000007',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $otherUser = AppUser::query()->create([
            'name' => 'Other User',
            'username' => 'otheruser',
            'phone' => '01000000008',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Sanctum::actingAs($authenticatedUser);

        $this->deleteJson('/api/app-user/profile/'.$otherUser->id)
            ->assertForbidden();

        $this->assertDatabaseHas('app_users', ['id' => $otherUser->id]);
    }

    public function test_app_user_can_update_own_profile_by_id(): void
    {
        $appUser = AppUser::query()->create([
            'name' => 'Old Name',
            'username' => 'oldname',
            'phone' => '01000000009',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Sanctum::actingAs($appUser);

        $this->postJson('/api/app-user/profile/'.$appUser->id, [
            'name' => 'New Name',
            'username' => 'newname',
            'phone' => '01000000009',
        ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.profile.id', $appUser->id)
            ->assertJsonPath('data.profile.name', 'New Name')
            ->assertJsonPath('data.profile.username', 'newname');

        $this->assertDatabaseHas('app_users', [
            'id' => $appUser->id,
            'name' => 'New Name',
            'username' => 'newname',
        ]);
    }

    public function test_app_user_cannot_update_another_profile_by_id(): void
    {
        $authenticatedUser = AppUser::query()->create([
            'name' => 'Authenticated User',
            'username' => 'authuser2',
            'phone' => '01000000010',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $otherUser = AppUser::query()->create([
            'name' => 'Other User',
            'username' => 'otheruser2',
            'phone' => '01000000011',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Sanctum::actingAs($authenticatedUser);

        $this->postJson('/api/app-user/profile/'.$otherUser->id, [
            'name' => 'Hacked Name',
            'phone' => '01000000011',
        ])->assertForbidden();

        $this->assertDatabaseHas('app_users', [
            'id' => $otherUser->id,
            'name' => 'Other User',
        ]);
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
