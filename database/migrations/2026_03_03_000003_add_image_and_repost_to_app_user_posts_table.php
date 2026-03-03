<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_posts', function (Blueprint $table) {
            $table->string('image')->nullable()->after('content');
            $table->foreignId('reposted_post_id')
                ->nullable()
                ->after('published_at')
                ->constrained('app_user_posts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_user_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reposted_post_id');
            $table->dropColumn('image');
        });
    }
};
