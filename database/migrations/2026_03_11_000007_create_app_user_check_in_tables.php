<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_check_in_cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('country_code', 2)->default('SA');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('radius_km', 8, 2)->default(30);
            $table->boolean('is_predefined')->default(true);
            $table->timestamps();
        });

        Schema::create('app_user_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_user_check_in_city_id')->constrained('app_user_check_in_cities')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('checked_in_at');
            $table->timestamps();
        });

        $now = now();
        $cities = [
            ['name' => 'Riyadh', 'slug' => 'riyadh', 'latitude' => 24.7136, 'longitude' => 46.6753],
            ['name' => 'Jeddah', 'slug' => 'jeddah', 'latitude' => 21.5433, 'longitude' => 39.1728],
            ['name' => 'Makkah', 'slug' => 'makkah', 'latitude' => 21.3891, 'longitude' => 39.8579],
            ['name' => 'Madinah', 'slug' => 'madinah', 'latitude' => 24.5247, 'longitude' => 39.5692],
            ['name' => 'Dammam', 'slug' => 'dammam', 'latitude' => 26.4207, 'longitude' => 50.0888],
            ['name' => 'Khobar', 'slug' => 'khobar', 'latitude' => 26.2172, 'longitude' => 50.1971],
            ['name' => 'Taif', 'slug' => 'taif', 'latitude' => 21.2703, 'longitude' => 40.4158],
            ['name' => 'Abha', 'slug' => 'abha', 'latitude' => 18.2164, 'longitude' => 42.5053],
            ['name' => 'Tabuk', 'slug' => 'tabuk', 'latitude' => 28.3998, 'longitude' => 36.5715],
            ['name' => 'Hail', 'slug' => 'hail', 'latitude' => 27.5114, 'longitude' => 41.7208],
            ['name' => 'Jazan', 'slug' => 'jazan', 'latitude' => 16.8892, 'longitude' => 42.5511],
            ['name' => 'Najran', 'slug' => 'najran', 'latitude' => 17.5650, 'longitude' => 44.2289],
            ['name' => 'Buraidah', 'slug' => 'buraidah', 'latitude' => 26.3592, 'longitude' => 43.9818],
        ];

        DB::table('app_user_check_in_cities')->insert(array_map(
            fn (array $city) => [
                ...$city,
                'country_code' => 'SA',
                'radius_km' => 30,
                'is_predefined' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $cities
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_check_ins');
        Schema::dropIfExists('app_user_check_in_cities');
    }
};
