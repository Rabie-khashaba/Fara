<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserCheckIn\StoreCheckInRequest;
use App\Models\AppUser;
use App\Models\AppUserCheckIn;
use App\Models\AppUserCheckInCity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AppUserCheckInController extends Controller
{
    public function cities(): JsonResponse
    {
        $cities = AppUserCheckInCity::query()
            ->withCount('checkIns')
            ->orderByDesc('check_ins_count')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'category',
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

    private function resolveCity(float $latitude, float $longitude, ?string $cityName): AppUserCheckInCity
    {
        $city = AppUserCheckInCity::query()
            ->where('country_code', 'SA')
            ->get()
            ->first(function (AppUserCheckInCity $city) use ($latitude, $longitude) {
                return $this->distanceKm($latitude, $longitude, $city->latitude, $city->longitude) <= $city->radius_km;
            });

        if ($city) {
            return $city;
        }

        $name = $cityName ?: sprintf('Custom City %.4f, %.4f', $latitude, $longitude);

        return AppUserCheckInCity::query()->create([
            'name' => $name,
            'category' => 'other',
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
}
