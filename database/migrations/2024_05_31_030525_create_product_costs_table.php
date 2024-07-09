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
        Schema::create('product_costs', function (Blueprint $table) {
            $table->id();
            $table->text('notes')->nullable();
            $table->decimal('price');
            $table->unsignedBigInteger('type_of_work_id');
            $table->foreign('type_of_work_id')->references('id')->on('type_of_works');
            $table->unsignedBigInteger('product_config_id');
            $table->foreign('product_config_id')->references('id')->on('product_configs');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_costs');
    }
};
