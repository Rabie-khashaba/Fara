<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_posts', function (Blueprint $table) {
            $table->index(['is_hide', 'created_at'], 'app_user_posts_visibility_created_at_index');
            $table->index(['app_user_id', 'created_at'], 'app_user_posts_app_user_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('app_user_posts', function (Blueprint $table) {
            $table->dropIndex('app_user_posts_visibility_created_at_index');
            $table->dropIndex('app_user_posts_app_user_created_at_index');
        });
    }
};
