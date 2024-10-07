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
      Schema::create('order_status', function (Blueprint $table) {
          $table->id();
          $table->string('status');
          $table->text('notes')->nullable();
          $table->unsignedBigInteger('order_id');
          $table->unsignedBigInteger('user_id');
          $table->softDeletes();
          $table->timestamps();

          $table->foreign('order_id')->references('id')->on('orders');
          $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status');
    }
};
