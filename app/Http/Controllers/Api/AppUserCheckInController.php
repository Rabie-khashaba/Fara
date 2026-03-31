<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserCheckIn\StoreCheckInRequest;
use App\Models\AppUser;
use App\Models\AppUserCheckIn;
use App\Models\AppUserCheckInCity;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AppUserCheckInController extends Controller
{
    public function cities(): JsonResponse
    {
        $now = $this->resolveNowForCheckIn();
        $hours = $this->resolveAvailabilityHours();
        $since = $now->copy()->subHours($hours);

        $cities = AppUserCheckInCity::query()
            ->addSelect([
                'check_ins_count' => AppUserCheckIn::query()
                    ->selectRaw('count(distinct app_user_id)')
                    ->whereColumn('app_user_check_ins.app_user_check_in_city_id', 'app_user_check_in_cities.id'),
                'available_users_count' => AppUserCheckIn::query()
                    ->selectRaw('count(distinct app_user_id)')
                    ->whereColumn('app_user_check_ins.app_user_check_in_city_id', 'app_user_check_in_cities.id')
                    ->whereBetween('checked_in_at', [$since, $now]),
            ])
            ->orderByDesc('check_ins_count')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'place_name',
                'slug',
                'country_code',
                'latitude',
                'longitude',
                'radius_km',
                'is_predefined',
            ]);

        return response()->json([
            'status' => true,
            'data' => $cities,
            'now' => $now->toIso8601String(),
            'hours' => $hours,
        ]);
    }

    public function index(): JsonResponse
    {
        $checkIns = AppUserCheckIn::query()
            ->with(['city:id,name,latitude,longitude', 'appUser:id,name,username'])
            ->latest('checked_in_at')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $checkIns,
        ]);
    }

    public function store(StoreCheckInRequest $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $data = $request->validated();

        $city = $this->resolveCity(
            (float) $data['latitude'],
            (float) $data['longitude'],
            $data['city_name'] ?? null
        );

        $checkIn = AppUserCheckIn::query()->create([
            'app_user_id' => $appUser->id,
            'app_user_check_in_city_id' => $city->id,
            'place_name' => $data['place_name'] ?? null,
            'category' => $data['category'] ?? 'other',
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'checked_in_at' => $data['checked_in_at'] ?? now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Check-in created successfully',
            'data' => $checkIn->load('city:id,name,latitude,longitude'),
        ], 201);
    }

    public function storeByCity(StoreCheckInRequest $request, AppUserCheckInCity $city): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $data = $request->validated();

        $checkIn = AppUserCheckIn::query()->create([
            'app_user_id' => $appUser->id,
            'app_user_check_in_city_id' => $city->id,
            'place_name' => $data['place_name'] ?? null,
            'category' => $data['category'] ?? 'other',
            'latitude' => $city->latitude,
            'longitude' => $city->longitude,
            'checked_in_at' => $data['checked_in_at'] ?? now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'City check-in created successfully',
            'data' => $checkIn->load('city:id,name,latitude,longitude'),
        ], 201);
    }

    public function availableUsers(Request $request, AppUserCheckIn $checkIn): JsonResponse
    {
        $now = $this->resolveNowForCheckIn();
        $hours = $this->resolveAvailabilityHours();
        $since = $now->copy()->subHours($hours);

        $userIds = AppUserCheckIn::query()
            ->select('app_user_id')
            ->where('app_user_check_in_city_id', $checkIn->app_user_check_in_city_id)
            ->whereBetween('checked_in_at', [$since, $now])
            ->distinct();

        $users = AppUser::query()
            ->whereIn('id', $userIds)
            ->get();

        return response()->json([
            'status' => true,
            'check_in_id' => $checkIn->id,
            'city_id' => $checkIn->app_user_check_in_city_id,
            'now' => $now->toIso8601String(),
            'hours' => $hours,
            'data' => $users,
        ]);
    }

    public function availableUsersByLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $now = now();
        $hours = AppSetting::getInt('checkin_availability_hours', 24);
        $hours = max(1, min(168, $hours));
        $since = $now->copy()->subHours($hours);

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        $availableUsersCount = AppUserCheckIn::query()
            ->selectRaw('count(distinct app_user_id) as aggregate')
            ->whereBetween('checked_in_at', [$since, $now])
            ->where('latitude', $latitude)
            ->where('longitude', $longitude)
            ->value('aggregate');

        $checkInId = AppUserCheckIn::query()
            ->whereBetween('checked_in_at', [$since, $now])
            ->where('latitude', $latitude)
            ->where('longitude', $longitude)
            ->latest('checked_in_at')
            ->value('id');

        return response()->json([
            'status' => true,
            'check_in_id' => $checkInId ? (int) $checkInId : null,
            'available_users_count' => (int) ($availableUsersCount ?? 0),
        ]);
    }

    public function availableUsersByLocationDetails(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $now = $this->resolveNowForCheckIn();
        $hours = $this->resolveAvailabilityHours();
        $since = $now->copy()->subHours($hours);

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        $userIds = AppUserCheckIn::query()
            ->select('app_user_id')
            ->whereBetween('checked_in_at', [$since, $now])
            ->where('latitude', $latitude)
            ->where('longitude', $longitude)
            ->distinct();

        $users = AppUser::query()
            ->whereIn('id', $userIds)
            ->get();

        $checkInId = AppUserCheckIn::query()
            ->whereBetween('checked_in_at', [$since, $now])
            ->where('latitude', $latitude)
            ->where('longitude', $longitude)
            ->latest('checked_in_at')
            ->value('id');

        return response()->json([
            'status' => true,
            'check_in_id' => $checkInId ? (int) $checkInId : null,
            'now' => $now->toIso8601String(),
            'hours' => $hours,
            'data' => $users,
        ]);
    }

    private function resolveCity(
        float $latitude,
        float $longitude,
        ?string $cityName
    ): AppUserCheckInCity
    {
        $normalizedCityName = $cityName ? trim($cityName) : null;

        if ($normalizedCityName) {
            $cityByName = AppUserCheckInCity::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($normalizedCityName)])
                ->first();

            if ($cityByName) {
                return $cityByName;
            }
        }

        $city = AppUserCheckInCity::query()
            ->where('country_code', 'SA')
            ->get()
            ->first(function (AppUserCheckInCity $city) use ($latitude, $longitude) {
                return $this->distanceKm($latitude, $longitude, $city->latitude, $city->longitude) <= $city->radius_km;
            });

        if ($city) {
            return $city;
        }

        $name = $normalizedCityName ?: sprintf('Custom City %.4f, %.4f', $latitude, $longitude);

        return AppUserCheckInCity::query()->create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'country_code' => 'SA',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_km' => 30,
            'is_predefined' => false,
        ]);
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    // Removed city lookup for location-based availability.

    private function resolveNowForCheckIn(): Carbon
    {
        $nowParam = request()->query('now');

        if (! empty($nowParam)) {
            try {
                return Carbon::parse($nowParam);
            } catch (\Throwable) {
                // Fall through to default now()
            }
        }

        return now();
    }

    private function resolveAvailabilityHours(): int
    {
        $hoursParam = request()->query('hours');

        if (is_numeric($hoursParam)) {
            $hours = (int) $hoursParam;

            if ($hours < 1) {
                return 1;
            }

            return min($hours, 168);
        }

        $configuredHours = AppSetting::getInt('checkin_availability_hours', 24);

        return max(1, min(168, $configuredHours));
    }
}
