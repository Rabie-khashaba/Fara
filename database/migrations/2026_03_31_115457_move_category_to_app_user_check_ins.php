<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_check_ins', function (Blueprint $table) {
            $table->string('category')->default('other')->after('place_name');
        });

        DB::table('app_user_check_ins')
            ->join('app_user_check_in_cities', 'app_user_check_ins.app_user_check_in_city_id', '=', 'app_user_check_in_cities.id')
            ->update(['app_user_check_ins.category' => DB::raw('app_user_check_in_cities.category')]);

        Schema::table('app_user_check_in_cities', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('app_user_check_in_cities', function (Blueprint $table) {
            $table->string('category')->default('other')->after('name');
        });

        Schema::table('app_user_check_ins', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
