<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_notifications', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('data');
            $table->timestamp('read_at')->nullable()->after('is_read');
        });
    }

    public function down(): void
    {
        Schema::table('app_user_notifications', function (Blueprint $table) {
            $table->dropColumn(['is_read', 'read_at']);
        });
    }
};
