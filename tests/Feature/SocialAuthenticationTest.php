<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_route_sends_user_to_provider(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')->once()->with(['openid', 'profile', 'email'])->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));

        $this->mock(SocialiteFactory::class, function (Mockery\MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')->once()->with('google')->andReturn($provider);
        });

        $this->get(route('auth.social.redirect', 'google'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_existing_admin_user_can_login_with_social_account_and_it_is_linked(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'type' => 'admin',
            'is_active' => true,
        ]);

        $providerUser = $this->makeSocialiteUser(
            id: 'google-123',
            email: 'admin@example.com',
            name: 'Admin User',
            avatar: 'https://cdn.example.com/avatar.png',
        );

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')->once()->with(['openid', 'profile', 'email'])->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($providerUser);

        $this->mock(SocialiteFactory::class, function (Mockery\MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')->once()->with('google')->andReturn($provider);
        });

        $this->get(route('auth.social.callback', 'google'))
            ->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('user_social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-123',
            'provider_email' => 'admin@example.com',
        ]);
    }

    public function test_existing_linked_user_can_login_with_apple_callback_post(): void
    {
        $user = User::factory()->create([
            'email' => 'apple-admin@example.com',
            'type' => 'admin',
            'is_active' => true,
        ]);

        $user->socialAccounts()->create([
            'provider' => 'apple',
            'provider_user_id' => 'apple-123',
            'provider_email' => 'apple-admin@example.com',
        ]);

        $providerUser = $this->makeSocialiteUser(
            id: 'apple-123',
            email: null,
            name: 'Apple Admin',
            avatar: null,
        );

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')->once()->with(['name', 'email'])->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($providerUser);

        $this->mock(SocialiteFactory::class, function (Mockery\MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')->once()->with('apple')->andReturn($provider);
        });

        $this->post(route('auth.social.callback', 'apple'))
            ->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
    }

    public function test_social_login_is_rejected_when_auto_creation_is_disabled_and_no_user_matches(): void
    {
        config()->set('social-auth.auto_create_users', false);

        $providerUser = $this->makeSocialiteUser(
            id: 'facebook-123',
            email: 'new-user@example.com',
            name: 'New User',
            avatar: null,
        );

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')->once()->with(['email', 'public_profile'])->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($providerUser);

        $this->mock(SocialiteFactory::class, function (Mockery\MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')->once()->with('facebook')->andReturn($provider);
        });

        $this->from(route('login'))
            ->get(route('auth.social.callback', 'facebook'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('social');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'new-user@example.com']);
    }

    public function test_social_login_can_auto_create_admin_user_when_enabled(): void
    {
        config()->set('social-auth.auto_create_users', true);

        $providerUser = $this->makeSocialiteUser(
            id: 'google-777',
            email: 'created@example.com',
            name: 'Created User',
            avatar: 'https://cdn.example.com/created.png',
        );

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')->once()->with(['openid', 'profile', 'email'])->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($providerUser);

        $this->mock(SocialiteFactory::class, function (Mockery\MockInterface $mock) use ($provider): void {
            $mock->shouldReceive('driver')->once()->with('google')->andReturn($provider);
        });

        $this->get(route('auth.social.callback', 'google'))
            ->assertRedirect('/home');

        $this->assertDatabaseHas('users', [
            'email' => 'created@example.com',
            'name' => 'Created User',
            'type' => 'admin',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('user_social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-777',
            'provider_email' => 'created@example.com',
        ]);
    }

    public function test_unsupported_provider_route_falls_back_to_login_redirect(): void
    {
        $this->get('/login/github/redirect')->assertRedirect(route('login'));
    }

    protected function makeSocialiteUser(
        string $id,
        ?string $email,
        ?string $name,
        ?string $avatar,
    ): SocialiteUser {
        return (new SocialiteUser())
            ->setRaw(['id' => $id, 'email' => $email, 'name' => $name])
            ->map([
                'id' => $id,
                'email' => $email,
                'name' => $name,
                'avatar' => $avatar,
            ])
            ->setToken('access-token')
            ->setRefreshToken('refresh-token')
            ->setExpiresIn(3600);
    }
}
