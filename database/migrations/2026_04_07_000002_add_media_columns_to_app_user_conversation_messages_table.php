<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_conversation_messages', function (Blueprint $table) {
            $table->json('image')->nullable()->after('body');
            $table->string('video')->nullable()->after('image');
            $table->json('contact')->nullable()->after('video');
            $table->decimal('latitude', 10, 7)->nullable()->after('contact');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('video_opened_at')->nullable()->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('app_user_conversation_messages', function (Blueprint $table) {
            $table->dropColumn([
                'image',
                'video',
                'contact',
                'latitude',
                'longitude',
                'video_opened_at',
            ]);
        });
    }
};
