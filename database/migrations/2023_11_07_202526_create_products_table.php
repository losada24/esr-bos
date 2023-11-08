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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('system');
            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->string('line_item_name');
            $table->string('frame_color');
            $table->integer('qty');
            $table->decimal('markup', 10, 2);
            $table->string('glass_type');
            $table->string('glass_color');
            $table->string('low_e');
            $table->string('privacy');
            $table->json('extras')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
