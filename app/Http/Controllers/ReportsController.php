<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserCheckIn;
use App\Models\AppUserCheckInCity;
use App\Models\AppUserPost;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(): View
    {
        $topPlaces = AppUserCheckInCity::query()
            ->withCount('checkIns')
            ->orderByDesc('check_ins_count')
            ->orderBy('name')
            ->take(5)
            ->get();

        $topCities = AppUserCheckInCity::query()
            ->select('id', 'name', 'country_code')
            ->withCount('checkIns')
            ->where('country_code', 'SA')
            ->orderByDesc('check_ins_count')
            ->orderBy('name')
            ->take(5)
            ->get();

        $mostActiveUsers = AppUser::query()
            ->withCount(['activities', 'posts', 'checkIns'])
            ->orderByDesc('activities_count')
            ->orderByDesc('posts_count')
            ->orderByDesc('check_ins_count')
            ->take(5)
            ->get()
            ->map(function (AppUser $user) {
                $user->activity_score = (int) $user->activities_count + (int) $user->posts_count + (int) $user->check_ins_count;

                return $user;
            })
            ->sortByDesc('activity_score')
            ->values();

        $startDate = now()->subDays(4)->startOfDay();
        $dailyCheckIns = AppUserCheckIn::query()
            ->selectRaw('DATE(checked_in_at) as report_date, COUNT(*) as total')
            ->where('checked_in_at', '>=', $startDate)
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->pluck('total', 'report_date');

        $dailyCheckInRows = collect(range(0, 4))
            ->map(function (int $offset) use ($startDate, $dailyCheckIns) {
                $date = $startDate->copy()->addDays($offset);
                $key = $date->toDateString();

                return [
                    'date' => $date->format('d M Y'),
                    'count' => (int) ($dailyCheckIns[$key] ?? 0),
                ];
            });

        $totalPosts = AppUserPost::query()->count();
        $publishedPosts = AppUserPost::query()->where('status', 'published')->count();
        $totalCheckIns = AppUserCheckIn::query()->count();
        $totalActivities = AppUserActivity::query()->count();

        return view('reports.index', [
            'topPlaces' => $topPlaces,
            'topCities' => $topCities,
            'mostActiveUsers' => $mostActiveUsers,
            'dailyCheckInRows' => $dailyCheckInRows,
            'totalPosts' => number_format($totalPosts),
            'publishedPosts' => number_format($publishedPosts),
            'totalCheckIns' => number_format($totalCheckIns),
            'totalActivities' => number_format($totalActivities),
        ]);
    }
}
