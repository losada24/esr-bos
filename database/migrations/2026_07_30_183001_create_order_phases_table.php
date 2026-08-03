<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('name');
            $table->string('status')->default('PLANNED');
            $table->date('delivery_date')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('installation_end_date')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('hide_on_weekends')->default(false);
            $table->json('replanned_reasons')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_email_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id', 'position']);
            $table->index(['status', 'installation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_phases');
    }
};
