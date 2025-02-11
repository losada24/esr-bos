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
        $table->date('service_date')->nullable();
      
    });
        Schema::table('order_status', function (Blueprint $table) {
          $table->date('service_date')->nullable();
        
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('service_date');
    
    });
        Schema::table('order_status', function (Blueprint $table) {
          $table->dropColumn('service_date');
      
      });
    }
};
