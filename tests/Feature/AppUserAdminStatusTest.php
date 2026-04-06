<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppUserAdminStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_deactivation_revokes_all_app_user_tokens(): void
    {
        $admin = User::factory()->create();
        $appUser = AppUser::query()->create([
            'name' => 'Reported User',
            'username' => 'revokeme',
            'phone' => '01030000001',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $appUser->createToken('mobile-1');
        $appUser->createToken('mobile-2');

        $this->actingAs($admin)
            ->patch("/app-users/{$appUser->id}/toggle-status")
            ->assertRedirect();

        $this->assertDatabaseHas('app_users', [
            'id' => $appUser->id,
            'is_active' => false,
            'inactive_reason' => AppUser::INACTIVE_REASON_ADMIN,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => AppUser::class,
            'tokenable_id' => $appUser->id,
        ]);
    }
}
