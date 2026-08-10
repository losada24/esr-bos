<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overdue_report_email_schedules', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->time('send_time')->default('08:00:00');
            $table->string('timezone')->default('America/New_York');
            $table->json('days_of_week')->nullable();
            $table->json('user_recipient_ids')->nullable();
            $table->json('manual_recipients')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overdue_report_email_schedules');
    }
};
