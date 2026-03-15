<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_user_conversations')) {
            Schema::create('app_user_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('type')->default('direct');
                $table->foreignId('created_by_app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('app_user_conversation_participants')) {
            Schema::create('app_user_conversation_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_user_conversation_id')->constrained('app_user_conversations')->cascadeOnDelete();
                $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
                $table->timestamp('last_read_at')->nullable();
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['app_user_conversation_id', 'app_user_id'],
                    'app_user_conversation_participants_unique'
                );
            });
        }

        if (! Schema::hasTable('app_user_conversation_messages')) {
            Schema::create('app_user_conversation_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_user_conversation_id')->constrained('app_user_conversations')->cascadeOnDelete();
                $table->foreignId('sender_app_user_id')->constrained('app_users')->cascadeOnDelete();
                $table->string('type')->default('text');
                $table->text('body');
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['app_user_conversation_id', 'created_at'], 'app_user_conversation_messages_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_conversation_messages');
        Schema::dropIfExists('app_user_conversation_participants');
        Schema::dropIfExists('app_user_conversations');
    }
};
