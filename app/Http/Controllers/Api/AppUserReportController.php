<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserReport\StoreAppUserReportRequest;
use App\Models\AppUser;
use App\Models\AppUserReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserReportController extends Controller
{
    public function types(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'types' => AppUserReport::TYPES,
                'block_threshold' => $this->blockThreshold(),
            ],
        ]);
    }

    public function store(StoreAppUserReportRequest $request, int $appUserId): JsonResponse
    {
        /** @var AppUser $reporter */
        $reporter = $request->user();
        $reportedUser = AppUser::query()->findOrFail($appUserId);

        abort_if($reporter->is($reportedUser), 422, 'You cannot report yourself');

        $report = AppUserReport::query()->updateOrCreate(
            [
                'reporter_app_user_id' => $reporter->id,
                'reported_app_user_id' => $reportedUser->id,
            ],
            $request->validated()
        );

        $reportsCount = AppUserReport::query()
            ->where('reported_app_user_id', $reportedUser->id)
            ->count();

        if ($reportsCount >= $this->blockThreshold() && $reportedUser->is_active) {
            $reportedUser->update([
                'is_active' => false,
                'inactive_reason' => AppUser::INACTIVE_REASON_REPORTS,
            ]);
        }

        $reportedUser->refresh();

        return response()->json([
            'status' => true,
            'message' => $report->wasRecentlyCreated ? 'User reported successfully' : 'User report updated successfully',
            'data' => [
                'report' => [
                    'id' => $report->id,
                    'report_type' => $report->report_type,
                    'details' => $report->details,
                    'reported_app_user_id' => $report->reported_app_user_id,
                    'reporter_app_user_id' => $report->reporter_app_user_id,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ],
                'reported_user' => [
                    'id' => $reportedUser->id,
                    'is_active' => (bool) $reportedUser->is_active,
                    'reports_count' => $reportsCount,
                    'block_threshold' => $this->blockThreshold(),
                ],
            ],
        ], $report->wasRecentlyCreated ? 201 : 200);
    }

    private function blockThreshold(): int
    {
        return max(1, (int) config('moderation.app_user_report_block_threshold', 1));
    }
}
