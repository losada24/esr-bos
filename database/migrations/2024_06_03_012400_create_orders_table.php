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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number');
            $table->string('name');
            $table->string('job_address', 500);
            $table->boolean('city_permits')->default(false);
            $table->boolean('association_permits')->default(false);
            $table->boolean('equipment_rental')->default(false);
            $table->decimal('equipment_rental_price')->nullable();
            $table->decimal('additional_travel_costs')->nullable();
            $table->date('entry_date');
            $table->date('installation_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('type_of_work_id');
            $table->unsignedBigInteger('type_of_housing_id');
            $table->unsignedBigInteger('installer_id');
            $table->unsignedBigInteger('supervisor_id');
            $table->unsignedBigInteger('travel_cost_id');
            $table->unsignedBigInteger('duration_of_work_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('type_of_work_id')->references('id')->on('type_of_works');
            $table->foreign('type_of_housing_id')->references('id')->on('types_of_housing');
            $table->foreign('installer_id')->references('id')->on('users');
            $table->foreign('supervisor_id')->references('id')->on('users');
            $table->foreign('travel_cost_id')->references('id')->on('travel_costs');
            $table->foreign('duration_of_work_id')->references('id')->on('duration_of_works');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('order_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('file_size');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders');
        });
        
        Schema::create('owner_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_attachments');
        Schema::dropIfExists('owner_user');
    }
};
