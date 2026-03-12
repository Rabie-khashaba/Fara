<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['app_user_id', 'package_id']);
        });

        if (Schema::hasColumn('app_users', 'package_id')) {
            DB::table('app_users')
                ->whereNotNull('package_id')
                ->orderBy('id')
                ->get(['id', 'package_id', 'created_at', 'updated_at'])
                ->each(function ($appUser) {
                    DB::table('app_user_package')->updateOrInsert(
                        [
                            'app_user_id' => $appUser->id,
                            'package_id' => $appUser->package_id,
                        ],
                        [
                            'created_at' => $appUser->created_at ?? now(),
                            'updated_at' => $appUser->updated_at ?? now(),
                        ]
                    );
                });

            Schema::table('app_users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('package_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('app_users', 'package_id')) {
            Schema::table('app_users', function (Blueprint $table) {
                $table->foreignId('package_id')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('packages')
                    ->nullOnDelete();
            });
        }

        DB::table('app_user_package')
            ->orderBy('id')
            ->get(['app_user_id', 'package_id'])
            ->each(function ($pivot) {
                DB::table('app_users')
                    ->where('id', $pivot->app_user_id)
                    ->whereNull('package_id')
                    ->update(['package_id' => $pivot->package_id]);
            });

        Schema::dropIfExists('app_user_package');
    }
};
