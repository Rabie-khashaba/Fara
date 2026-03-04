<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_reposts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_user_post_id')->constrained('app_user_posts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['app_user_id', 'app_user_post_id'], 'app_user_reposts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_reposts');
    }
};
