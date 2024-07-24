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
        Schema::table('orders', function (Blueprint $table) {
            $table->date('eta_date')->nullable()->after('installation_date');
        });

        Schema::create('config_date_estimation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('travel_cost_id');
            $table->unsignedBigInteger('type_of_housing_id');
            $table->integer('weeks');
            $table->string('week_day');
            $table->integer('days_difference_between_services')->default(1);
            $table->timestamps();

            $table->foreign('travel_cost_id')->references('id')->on('travel_costs');
            $table->foreign('type_of_housing_id')->references('id')->on('types_of_housing');
        });

        Schema::create('extra_work_type_of_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extra_work_id');
            $table->unsignedBigInteger('type_of_product_id');
            $table->timestamps();

            $table->foreign('extra_work_id')->references('id')->on('extra_works');
            $table->foreign('type_of_product_id')->references('id')->on('type_of_products');
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('eta_date');
      });

      Schema::dropIfExists('config_date_estimation');
      Schema::dropIfExists('extra_work_type_of_products');
    }
};
