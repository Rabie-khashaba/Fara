<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('provider')->nullable()->after('expired_otp_at');
            $table->string('provider_id')->nullable()->after('provider');
            $table->string('api_token', 80)->nullable()->unique()->after('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['api_token']);
            $table->dropColumn(['username', 'provider', 'provider_id', 'api_token']);
        });
    }
};
