<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\AppUserReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppUserReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_types_endpoint_returns_allowed_types_and_threshold(): void
    {
        config(['moderation.app_user_report_block_threshold' => 3]);

        $this->getJson('/api/app-user/report-types')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.types', AppUserReport::TYPES)
            ->assertJsonPath('data.block_threshold', 3);
    }

    public function test_app_user_can_report_another_app_user(): void
    {
        $reporter = $this->createAppUser('Reporter', 'reporter1', '01020000001');
        $reported = $this->createAppUser('Reported', 'reported1', '01020000002');

        Sanctum::actingAs($reporter);

        $this->postJson("/api/app-user/reports/users/{$reported->id}", [
            'report_type' => AppUserReport::TYPE_HARASSMENT,
            'details' => 'Repeated abusive messages.',
        ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.report.report_type', AppUserReport::TYPE_HARASSMENT)
            ->assertJsonPath('data.reported_user.reports_count', 1)
            ->assertJsonPath('data.reported_user.is_active', true);

        $this->assertDatabaseHas('app_user_reports', [
            'reporter_app_user_id' => $reporter->id,
            'reported_app_user_id' => $reported->id,
            'report_type' => AppUserReport::TYPE_HARASSMENT,
        ]);
    }

    public function test_reporting_same_user_twice_updates_existing_report_instead_of_creating_duplicate(): void
    {
        $reporter = $this->createAppUser('Reporter', 'reporter2', '01020000003');
        $reported = $this->createAppUser('Reported', 'reported2', '01020000004');

        Sanctum::actingAs($reporter);

        $this->postJson("/api/app-user/reports/users/{$reported->id}", [
            'report_type' => AppUserReport::TYPE_OTHER,
            'details' => 'First note.',
        ])->assertCreated();

        $this->postJson("/api/app-user/reports/users/{$reported->id}", [
            'report_type' => AppUserReport::TYPE_HATE_SPEECH,
            'details' => 'Updated note.',
        ])
            ->assertOk()
            ->assertJsonPath('data.report.report_type', AppUserReport::TYPE_HATE_SPEECH)
            ->assertJsonPath('data.reported_user.reports_count', 1);

        $this->assertDatabaseCount('app_user_reports', 1);
        $this->assertDatabaseHas('app_user_reports', [
            'reporter_app_user_id' => $reporter->id,
            'reported_app_user_id' => $reported->id,
            'report_type' => AppUserReport::TYPE_HATE_SPEECH,
            'details' => 'Updated note.',
        ]);
    }

    public function test_app_user_cannot_report_self(): void
    {
        $appUser = $this->createAppUser('Self Reporter', 'selfreporter', '01020000005');

        Sanctum::actingAs($appUser);

        $this->postJson("/api/app-user/reports/users/{$appUser->id}", [
            'report_type' => AppUserReport::TYPE_VIOLENCE,
        ])->assertStatus(422);

        $this->assertDatabaseCount('app_user_reports', 0);
    }

    public function test_reported_app_user_is_blocked_when_reports_reach_threshold(): void
    {
        config(['moderation.app_user_report_block_threshold' => 2]);

        $reported = $this->createAppUser('Reported', 'reported3', '01020000006');
        $firstReporter = $this->createAppUser('Reporter A', 'reportera', '01020000007');
        $secondReporter = $this->createAppUser('Reporter B', 'reporterb', '01020000008');

        Sanctum::actingAs($firstReporter);
        $this->postJson("/api/app-user/reports/users/{$reported->id}", [
            'report_type' => AppUserReport::TYPE_INAPPROPRIATE_CONTENT,
        ])
            ->assertCreated()
            ->assertJsonPath('data.reported_user.is_active', true);

        Sanctum::actingAs($secondReporter);
        $this->postJson("/api/app-user/reports/users/{$reported->id}", [
            'report_type' => AppUserReport::TYPE_VIOLENCE,
        ])
            ->assertCreated()
            ->assertJsonPath('data.reported_user.is_active', false)
            ->assertJsonPath('data.reported_user.reports_count', 2);

        $this->assertDatabaseHas('app_users', [
            'id' => $reported->id,
            'is_active' => false,
        ]);
    }

    private function createAppUser(string $name, string $username, string $phone): AppUser
    {
        return AppUser::query()->create([
            'name' => $name,
            'username' => $username,
            'phone' => $phone,
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
