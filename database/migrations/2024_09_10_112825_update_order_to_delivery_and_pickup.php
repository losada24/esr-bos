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
            $table->unsignedBigInteger('type_of_work_id')->nullable()->change();
            $table->unsignedBigInteger('type_of_housing_id')->nullable()->change();
            $table->unsignedBigInteger('travel_cost_id')->nullable()->change();
            $table->unsignedBigInteger('duration_of_work_id')->nullable()->change();
            $table->date('installation_date')->nullable()->change();
            $table->date('installation_end_date')->nullable()->change();
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->unsignedBigInteger('type_of_work_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
           
        });
    }
};
