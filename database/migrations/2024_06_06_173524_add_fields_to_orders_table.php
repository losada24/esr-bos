<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enum\MethodOfPayment;
use App\Enum\Service;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('method_of_payment', [
                MethodOfPayment::CASH->value,
                MethodOfPayment::FINANCED->value
            ]);
            $table->enum('service', [
                Service::DELIVERY->value,
                Service::INSTALLATION->value
            ]);
            $table->date('contract_signing_date');
            $table->date('payment_factory_date');
            $table->date('delivery_date');
            $table->date('installation_date')->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('method_of_payment');
            $table->dropColumn('process');
            $table->dropColumn('contract_signing_date');
            $table->dropColumn('delivery_date');
            $table->dropColumn('payment_factory_date');
            $table->date('installation_date')->nullable();
        });
    }
};
