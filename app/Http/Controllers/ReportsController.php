<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppUserCheckIn;
use App\Models\AppUserCheckInCity;
use App\Models\AppUserPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('reports.top-places');
    }

    public function topPlaces(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $places = AppUserCheckInCity::query()
            ->withCount([
                'checkIns as filtered_check_ins_count' => fn (Builder $query) => $this->applyCheckInDateFilter($query, $dateFrom, $dateTo),
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderByDesc('filtered_check_ins_count')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('reports.index', [
            'reportTitle' => 'Top Places Report',
            'theme' => 'info',
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'resetRoute' => route('reports.top-places'),
            'filterAction' => route('reports.top-places'),
            'filters' => ['search', 'date_range'],
            'filterOptions' => [],
            'summaryCards' => [[
                'label' => 'Check-ins In Range',
                'value' => number_format(
                AppUserCheckIn::query()
                    ->whereBetween('checked_in_at', [$dateFrom, $dateTo])
                    ->count()
                ),
            ]],
            'columns' => ['Place', 'Country', 'Check-ins'],
            'rows' => $places->through(fn ($place) => [
                $place->name,
                $place->country_code,
                number_format($place->filtered_check_ins_count),
            ]),
            'paginator' => $places,
        ]);
    }

    public function topCities(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $cities = AppUserCheckInCity::query()
            ->select('id', 'name', 'country_code', 'latitude', 'longitude')
            ->withCount([
                'checkIns as filtered_people_count' => fn (Builder $query) => $query
                    ->select(DB::raw('COUNT(DISTINCT app_user_id)'))
                    ->whereBetween('checked_in_at', [$dateFrom, $dateTo]),
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('country_code'), fn (Builder $query) => $query->where('country_code', strtoupper((string) $request->string('country_code'))))
            ->orderByDesc('filtered_people_count')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('reports.index', [
            'reportTitle' => 'Top Cities Report',
            'theme' => 'info',
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'resetRoute' => route('reports.top-cities'),
            'filterAction' => route('reports.top-cities'),
            'filters' => ['search', 'country_code', 'date_range'],
            'filterOptions' => [
                'countries' => AppUserCheckInCity::query()
                    ->select('country_code')
                    ->distinct()
                    ->orderBy('country_code')
                    ->pluck('country_code'),
            ],
            'summaryCards' => [],
            'columns' => ['City', 'Country', 'Latitude', 'Longitude', 'People'],
            'rows' => $cities->through(fn ($city) => [
                $city->name,
                $city->country_code,
                number_format((float) $city->latitude, 4),
                number_format((float) $city->longitude, 4),
                number_format($city->filtered_people_count),
            ]),
            'paginator' => $cities,
        ]);
    }

    public function activeUsers(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $users = AppUser::query()
            ->withCount([
                'activities as filtered_activities_count' => fn (Builder $query) => $query->whereBetween('created_at', [$dateFrom, $dateTo]),
                'posts as filtered_posts_count' => fn (Builder $query) => $query->whereBetween('created_at', [$dateFrom, $dateTo]),
                'checkIns as filtered_check_ins_count' => fn (Builder $query) => $query->whereBetween('checked_in_at', [$dateFrom, $dateTo]),
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(in_array($request->input('status'), ['active', 'inactive'], true), function (Builder $query) use ($request) {
                $query->where('is_active', $request->input('status') === 'active');
            })
            ->orderByDesc('filtered_activities_count')
            ->orderByDesc('filtered_posts_count')
            ->orderByDesc('filtered_check_ins_count')
            ->paginate(10)
            ->through(function (AppUser $user) {
                $user->activity_score = (int) $user->filtered_activities_count + (int) $user->filtered_posts_count + (int) $user->filtered_check_ins_count;

                return $user;
            })
            ->withQueryString();

        return view('reports.index', [
            'reportTitle' => 'Most Active Users Report',
            'theme' => 'info',
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'resetRoute' => route('reports.active-users'),
            'filterAction' => route('reports.active-users'),
            'filters' => ['search', 'status', 'date_range'],
            'filterOptions' => [],
            'summaryCards' => [],
            'columns' => ['User', 'Status', 'Activities', 'Posts', 'Check-ins', 'Score'],
            'rows' => $users->through(fn ($user) => [
                $user->name,
                $user->is_active ? 'Active' : 'Inactive',
                number_format($user->filtered_activities_count),
                number_format($user->filtered_posts_count),
                number_format($user->filtered_check_ins_count),
                number_format($user->activity_score),
            ]),
            'paginator' => $users,
        ]);
    }

    public function dailyCheckIns(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 5);
        $cityId = $request->integer('city_id') ?: null;

        $checkIns = AppUserCheckIn::query()
            ->when($cityId, fn (Builder $query) => $query->where('app_user_check_in_city_id', $cityId))
            ->whereBetween('checked_in_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(checked_in_at) as report_date, COUNT(*) as total')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->pluck('total', 'report_date');

        $days = max(0, $dateFrom->diffInDays($dateTo));
        $rows = collect(range(0, $days))->map(function (int $offset) use ($dateFrom, $checkIns) {
            $date = $dateFrom->copy()->addDays($offset);
            $key = $date->toDateString();

            return [
                'date' => $date->format('d M Y'),
                'count' => (int) ($checkIns[$key] ?? 0),
            ];
        });

        return view('reports.index', [
            'reportTitle' => 'Daily Check-ins Report',
            'theme' => 'info',
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'resetRoute' => route('reports.daily-check-ins'),
            'filterAction' => route('reports.daily-check-ins'),
            'filters' => ['city_id', 'date_range'],
            'filterOptions' => [
                'cities' => AppUserCheckInCity::query()->orderBy('name')->get(['id', 'name']),
            ],
            'summaryCards' => [
                [
                    'label' => 'Total Check-ins',
                    'value' => number_format($rows->sum('count')),
                ],
                [
                    'label' => 'Peak Day',
                    'value' => ($rows->sortByDesc('count')->first()['date'] ?? '-') . ' | ' . number_format($rows->sortByDesc('count')->first()['count'] ?? 0),
                ],
            ],
            'columns' => ['Date', 'Check-ins'],
            'rows' => $rows->map(fn ($row) => [
                $row['date'],
                number_format($row['count']),
            ]),
            'paginator' => null,
        ]);
    }

    public function posts(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $posts = AppUserPost::query()
            ->with(['appUser:id,name,username'])
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('content', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhereHas('appUser', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when(in_array($request->input('ghost'), ['yes', 'no'], true), fn (Builder $query) => $query->where('is_ghost', $request->input('ghost') === 'yes'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $baseQuery = AppUserPost::query()->whereBetween('created_at', [$dateFrom, $dateTo]);

        return view('reports.index', [
            'reportTitle' => 'Posts Report',
            'theme' => 'info',
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'resetRoute' => route('reports.posts'),
            'filterAction' => route('reports.posts'),
            'filters' => ['search', 'status_select', 'ghost', 'date_range'],
            'filterOptions' => [],
            'summaryCards' => [
                ['label' => 'Total Posts', 'value' => number_format((clone $baseQuery)->count())],
                ['label' => 'Published', 'value' => number_format((clone $baseQuery)->where('status', 'published')->count())],
                ['label' => 'Ghost Posts', 'value' => number_format((clone $baseQuery)->where('is_ghost', true)->count())],
            ],
            'columns' => ['Author', 'Content', 'Status', 'Ghost', 'Likes', 'Comments', 'Shares', 'Saved', 'Created'],
            'rows' => $posts->through(fn ($post) => [
                $post->is_ghost ? 'Ghost User' : ($post->appUser?->name ?? 'Unknown'),
                str($post->content ?: 'No content')->limit(50),
                str($post->status)->headline(),
                $post->is_ghost ? 'Yes' : 'No',
                number_format($post->likes_count),
                number_format($post->comments_count),
                number_format($post->shared_posts_count),
                number_format($post->saved_posts_count),
                $post->created_at?->format('d M Y') ?: '-',
            ]),
            'paginator' => $posts,
        ]);
    }

    private function resolveDateRange(Request $request, int $defaultDays): array
    {
        $dateTo = $request->filled('date_to')
            ? Carbon::parse((string) $request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse((string) $request->input('date_from'))->startOfDay()
            : $dateTo->copy()->subDays($defaultDays - 1)->startOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [$dateFrom, $dateTo];
    }

    private function applyCheckInDateFilter(Builder $query, Carbon $dateFrom, Carbon $dateTo): void
    {
        $query->whereBetween('checked_in_at', [$dateFrom, $dateTo]);
    }
}
