<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->nullableMorphs('notifiable');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'read_at']);
            $table->index(['user_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_notifications');
    }
};
