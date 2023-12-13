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
        Schema::create('order_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id');
            $table->string('status');
            $table->json('order_products')->comment("Contains a json array with the order products details");
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')
              ->references('id')
              ->on('orders')
              ->cascadeOnDelete();
            $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_snapshots');
    }
};
