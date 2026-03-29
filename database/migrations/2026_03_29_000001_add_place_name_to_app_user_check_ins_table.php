<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_check_ins', function (Blueprint $table) {
            $table->string('place_name')->nullable()->after('app_user_check_in_city_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_user_check_ins', function (Blueprint $table) {
            $table->dropColumn('place_name');
        });
    }
};
