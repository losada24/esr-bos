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
        Schema::create('sale_forms', function (Blueprint $table) {
            $table->id();
            $table->boolean('sale')->nullable()->default(false);
            $table->boolean('installation')->nullable()->default(false);
            $table->boolean('permit')->nullable()->default(false);
            $table->boolean('replacement')->nullable()->default(false);
            $table->boolean('new_construction')->nullable()->default(false);
            $table->boolean('financing')->nullable()->default(false);
            $table->boolean('screen')->nullable()->default(false);
            $table->boolean('design')->nullable()->default(false);
            $table->boolean('mountin')->nullable()->default(false);
            $table->boolean('bar')->nullable()->default(false);
            $table->boolean('shutter_hole')->nullable()->default(false);
            $table->boolean('floor_cutting')->nullable()->default(false);
            $table->boolean('interior_finish')->nullable()->default(false);
            $table->string('floor')->nullable();
            $table->string('frame_color')->nullable();
            $table->string('glass_color')->nullable();
            $table->string('glass_type')->nullable();
            $table->string('glass_coating')->nullable();
            $table->integer('door_quantity')->nullable();
            $table->integer('window_quantity')->nullable();
             $table->unsignedBigInteger('order_id');
            $table->timestamps();
            $table->softDeletes();

             $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_forms');
    }
};
