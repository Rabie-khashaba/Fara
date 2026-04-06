<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->string('inactive_reason', 50)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->dropColumn('inactive_reason');
        });
    }
};
