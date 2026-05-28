<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('Scheduled');
            $table->boolean('is_repeating')->default(false);
            $table->boolean('reminder_enabled')->default(false);
            $table->unsignedInteger('reminder_minutes_before')->nullable();
            $table->string('location')->nullable();
            $table->boolean('online_meeting')->default(false);
            $table->string('meeting_link')->nullable();
            $table->json('participants')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['host_id', 'starts_at']);
            $table->index(['order_id', 'starts_at']);
            $table->index(['client_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_events');
    }
};
