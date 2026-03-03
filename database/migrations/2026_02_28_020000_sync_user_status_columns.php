<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('type');
            });
        }

        if (Schema::hasColumn('users', 'is_blocked')) {
            DB::statement('UPDATE users SET is_active = CASE WHEN is_blocked = 1 THEN 0 ELSE 1 END');

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_blocked');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'is_blocked')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_blocked')->default(false)->after('type');
            });
        }

        if (Schema::hasColumn('users', 'is_active')) {
            DB::statement('UPDATE users SET is_blocked = CASE WHEN is_active = 1 THEN 0 ELSE 1 END');

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
