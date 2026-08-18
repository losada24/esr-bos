<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_event_occurrence_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_event_id')->constrained('crm_events')->cascadeOnDelete();
            $table->dateTime('occurrence_starts_at');
            $table->dateTime('occurrence_ends_at');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['crm_event_id', 'occurrence_starts_at'], 'crm_event_occurrence_emails_unique_occurrence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_event_occurrence_emails');
    }
};
