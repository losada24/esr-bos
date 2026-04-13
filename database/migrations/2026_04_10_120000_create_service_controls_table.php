<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('service_type');
            $table->text('description')->nullable();
            $table->boolean('requires_part')->default(false);
            $table->boolean('requested_parts')->default(false);
            $table->boolean('parts_available')->default(false);
            $table->string('service_status');
            $table->string('priority');
            $table->date('target_date')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->date('executed_date')->nullable();
            $table->date('opened_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->string('closure_result')->nullable();
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_controls');
    }
};
