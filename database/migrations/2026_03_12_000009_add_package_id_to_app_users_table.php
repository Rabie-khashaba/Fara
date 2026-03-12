<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->foreignId('package_id')
                ->nullable()
                ->after('is_active')
                ->constrained('packages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
        });
    }
};
