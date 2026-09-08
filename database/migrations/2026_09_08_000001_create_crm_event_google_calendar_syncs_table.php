<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_event_google_calendar_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_event_id')->constrained('crm_events')->cascadeOnDelete();
            $table->string('user_email', 320);
            $table->string('google_event_id', 1024);
            $table->string('status', 32)->default('pending')->index();
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['crm_event_id', 'user_email'], 'crm_event_google_calendar_sync_unique_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_event_google_calendar_syncs');
    }
};
