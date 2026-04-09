<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_check_ins', function (Blueprint $table) {
            $table->foreignId('app_user_post_id')
                ->nullable()
                ->after('app_user_id')
                ->constrained('app_user_posts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_user_check_ins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('app_user_post_id');
        });
    }
};
