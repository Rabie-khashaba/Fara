<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->foreignId('reported_app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->string('report_type', 50);
            $table->text('details')->nullable();
            $table->timestamps();

            $table->unique(['reporter_app_user_id', 'reported_app_user_id'], 'app_user_reports_unique_reporter_reported');
            $table->index(['reported_app_user_id', 'report_type'], 'app_user_reports_reported_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_reports');
    }
};
