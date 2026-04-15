<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_control_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_control_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_type');
            $table->string('summary')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('service_control_id')->references('id')->on('service_controls');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_control_histories');
    }
};
