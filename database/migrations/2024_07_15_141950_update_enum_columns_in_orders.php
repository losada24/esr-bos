<?php

use App\Enum\MethodOfPayment;
use App\Enum\Service;
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
            $table->string('method_of_payment');
            $table->string('service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
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
        });
    }
};
