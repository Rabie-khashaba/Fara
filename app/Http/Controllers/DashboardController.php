<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppUserCheckInCity;
use App\Models\AppUserPost;
use App\Models\AppUserRepost;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function analytics(): View
    {
        $startMonth = now()->startOfMonth()->subMonths(11);
        $months = collect(range(0, 11))->map(
            fn (int $offset) => $startMonth->copy()->addMonths($offset)
        );

        $totalUsers = AppUser::query()->count();
        $activeUsers = AppUser::query()->where('is_active', true)->count();
        $newUsers = AppUser::query()->where('created_at', '>=', now()->subDays(30))->count();

        $totalPosts = AppUserPost::query()->count();
        $publishedPosts = AppUserPost::query()
            ->where('status', 'published')
            ->count();
        $totalReposts = AppUserRepost::query()->count();

        $usersByMonth = AppUser::query()
            ->where('created_at', '>=', $startMonth)
            ->get(['created_at'])
            ->countBy(fn (AppUser $user) => $this->monthKey($user->created_at));

        $postsByMonth = AppUserPost::query()
            ->where('created_at', '>=', $startMonth)
            ->get(['created_at'])
            ->countBy(fn (AppUserPost $post) => $this->monthKey($post->created_at));

        $saudiCities = AppUserCheckInCity::query()
            ->where('country_code', 'SA')
            ->withCount('checkIns')
            ->orderByDesc('check_ins_count')
            ->orderBy('name')
            ->get()
            ->map(function (AppUserCheckInCity $city) {
                return [
                    'name' => $city->name,
                    'coords' => [$city->latitude, $city->longitude],
                    'count' => $city->check_ins_count,
                ];
            })
            ->values();

        $maxCheckIns = max(1, (int) $saudiCities->max('count'));

        return view('dashboards.analytics', [
            'totalUsersCount' => number_format($totalUsers),
            'repostsCount' => number_format($totalReposts),
            'postsCount' => number_format($totalPosts),
            'newUsersCount' => number_format($newUsers),
            'performanceChart' => [
                'categories' => $months->map(fn (Carbon $month) => $month->format('M'))->all(),
                'users' => $months->map(fn (Carbon $month) => $usersByMonth->get($month->format('Y-m'), 0))->all(),
                'posts' => $months->map(fn (Carbon $month) => $postsByMonth->get($month->format('Y-m'), 0))->all(),
            ],
            'cityLocation' => $saudiCities,
            'cityLocationTop' => $saudiCities
                ->take(5)
                ->values()
                ->map(fn (array $city) => [
                    ...$city,
                    'percentage' => round(($city['count'] / $maxCheckIns) * 100, 1),
                ]),
            'saudiCitiesMap' => [
                'markers' => $saudiCities->map(fn (array $city) => [
                    'name' => $city['name'],
                    'coords' => $city['coords'],
                ])->values()->all(),
            ],
        ]);
    }

    private function monthKey(Carbon $date): string
    {
        return $date->format('Y-m');
    }
}
