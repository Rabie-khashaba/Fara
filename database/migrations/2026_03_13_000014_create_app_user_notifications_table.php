<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->foreignId('recipient_app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('target_fcm_token', 5000);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_notifications');
    }
};
