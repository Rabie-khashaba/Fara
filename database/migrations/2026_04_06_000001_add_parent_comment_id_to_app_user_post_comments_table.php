<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_post_comments', function (Blueprint $table) {
            $table->foreignId('parent_comment_id')
                ->nullable()
                ->after('app_user_id')
                ->constrained('app_user_post_comments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_user_post_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_comment_id');
        });
    }
};
