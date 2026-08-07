<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('overdue_report_email_schedules', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->json('weekdays')->nullable();
            $table->time('send_time')->default('08:00:00');
            $table->string('timezone')->default('America/New_York');
            $table->json('recipient_user_ids')->nullable();
            $table->json('manual_emails')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->date('last_sent_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overdue_report_email_schedules');
    }
};
