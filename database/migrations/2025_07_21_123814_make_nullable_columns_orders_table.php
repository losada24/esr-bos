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
        $table->string('order_number')->nullable()->change();
        $table->string('name')->nullable()->change();
        $table->string('job_address', 500)->nullable()->change();

        $table->boolean('city_permits')->nullable()->change();
        $table->boolean('association_permits')->nullable()->change();
        $table->boolean('equipment_rental')->nullable()->change();

        // Nuevos campos a hacer nullable
        $table->date('entry_date')->nullable()->change();
        $table->date('contract_signing_date')->nullable()->change();
        $table->date('payment_factory_date')->nullable()->change();
        $table->date('delivery_date')->nullable()->change();
        $table->string('method_of_payment')->nullable()->change();
        $table->string('service')->nullable()->change();
        $table->boolean('payment_definition')->nullable()->change(); // mantiene default(false)
        $table->decimal('initial_payment_percentage', 10, 2)->nullable()->change();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
        $table->string('order_number')->nullable(false)->change();
        $table->string('name')->nullable(false)->change();
        $table->string('job_address', 500)->nullable(false)->change();

        $table->boolean('city_permits')->nullable(false)->change();
        $table->boolean('association_permits')->nullable(false)->change();
        $table->boolean('equipment_rental')->nullable(false)->change();

        // Revertir los nuevos campos a NOT NULL
        $table->date('entry_date')->nullable(false)->change();
        $table->date('contract_signing_date')->nullable(false)->change();
        $table->date('payment_factory_date')->nullable(false)->change();
        $table->date('delivery_date')->nullable(false)->change();
        $table->string('method_of_payment')->nullable(false)->change();
        $table->string('service')->nullable(false)->change();
        $table->boolean('payment_definition')->nullable(false)->change(); // mantiene default(false)
        $table->decimal('initial_payment_percentage', 10, 2)->nullable(false)->change();
    });
    }
};
