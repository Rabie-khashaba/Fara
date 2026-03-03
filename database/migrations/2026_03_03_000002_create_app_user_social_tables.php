<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_user_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->foreignId('following_app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_app_user_id', 'following_app_user_id'], 'app_user_follows_unique');
        });

        Schema::create('app_user_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_post_id')->constrained('app_user_posts')->cascadeOnDelete();
            $table->foreignId('app_user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['app_user_post_id', 'app_user_id'], 'app_user_post_likes_unique');
        });

        Schema::create('app_user_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_post_id')->constrained('app_user_posts')->cascadeOnDelete();
            $table->foreignId('app_user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('app_user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('app_user_post_id')->nullable()->constrained('app_user_posts')->nullOnDelete();
            $table->foreignId('subject_app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_activities');
        Schema::dropIfExists('app_user_post_comments');
        Schema::dropIfExists('app_user_post_likes');
        Schema::dropIfExists('app_user_follows');
        Schema::dropIfExists('app_user_posts');
    }
};
