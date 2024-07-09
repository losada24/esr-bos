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
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_config_id');
            $table->unsignedBigInteger('type_of_work_id');
            $table->decimal('height', 8, 3);
            $table->decimal('width', 8, 3);
            $table->integer('qty');
            $table->decimal('unit_price');
            $table->decimal('total_price');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('product_config_id')->references('id')->on('product_configs');
            $table->foreign('type_of_work_id')->references('id')->on('type_of_works');
        });

        Schema::create('order_products_extra_works', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_product_id');
            $table->unsignedBigInteger('extra_work_id');
            $table->integer('number_of_sides')->default(0)->nullable();
            $table->decimal('price');

            $table->foreign('order_product_id')->references('id')->on('order_products');
            $table->foreign('extra_work_id')->references('id')->on('extra_works');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_products');
    }
};
