<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('app_users', 'is_active')) {
            Schema::table('app_users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('phone');
            });
        }

        if (Schema::hasColumn('app_users', 'is_blocked')) {
            DB::statement('UPDATE app_users SET is_active = CASE WHEN is_blocked = 1 THEN 0 ELSE 1 END');

            Schema::table('app_users', function (Blueprint $table) {
                $table->dropColumn('is_blocked');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('app_users', 'is_blocked')) {
            Schema::table('app_users', function (Blueprint $table) {
                $table->boolean('is_blocked')->default(false)->after('phone');
            });
        }

        if (Schema::hasColumn('app_users', 'is_active')) {
            DB::statement('UPDATE app_users SET is_blocked = CASE WHEN is_active = 1 THEN 0 ELSE 1 END');

            Schema::table('app_users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
