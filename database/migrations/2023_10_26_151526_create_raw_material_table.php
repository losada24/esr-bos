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
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit_of_measurement');
            $table->float('qty');
            $table->decimal('cost_per_unit', 10, 2);
            $table->string('notes')->nullable();
            $table->string('featured_image')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });

        /* Schema::create('daily_stock', function (Blueprint $table) {
          $table->id();
          $table->unsignedBigInteger('raw_material_id')->after('id');
          $table->float('qty');
          $table->decimal('cost', 10, 2);
          $table->unsignedBigInteger('user_id');
          $table->foreign('user_id')->references('id')->on('users');
          $table->foreign('raw_material_id')->references('id')->on('raw_materials');
          $table->softDeletes();
          $table->timestamps(); 
      });*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material');
    }
};
