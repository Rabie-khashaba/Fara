<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number', 32)->unique();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('subject', 255);
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_by_type', 20)->nullable();
            $table->unsignedBigInteger('closed_by_id')->nullable();
            $table->timestamps();

            $table->index(['app_user_id', 'status'], 'support_tickets_app_user_status_index');
            $table->index(['assigned_user_id', 'status'], 'support_tickets_assigned_user_status_index');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sender_app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at'], 'support_ticket_messages_ticket_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
