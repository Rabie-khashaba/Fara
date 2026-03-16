<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_posts', function (Blueprint $table) {
            $table->longText('image')->nullable()->change();
        });

        DB::table('app_user_posts')
            ->whereNotNull('image')
            ->orderBy('id')
            ->get(['id', 'image'])
            ->each(function (object $post): void {
                $decoded = json_decode($post->image, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return;
                }

                DB::table('app_user_posts')
                    ->where('id', $post->id)
                    ->update([
                        'image' => json_encode([$post->image], JSON_UNESCAPED_SLASHES),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('app_user_posts')
            ->whereNotNull('image')
            ->orderBy('id')
            ->get(['id', 'image'])
            ->each(function (object $post): void {
                $decoded = json_decode($post->image, true);

                if (! is_array($decoded)) {
                    return;
                }

                DB::table('app_user_posts')
                    ->where('id', $post->id)
                    ->update([
                        'image' => $decoded[0] ?? null,
                    ]);
            });

        Schema::table('app_user_posts', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
        });
    }
};
