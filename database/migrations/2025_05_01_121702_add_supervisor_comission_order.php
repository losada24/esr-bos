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
          Schema::table('supervisor_comission_orders', function (Blueprint $table) {
            $table->string('tier');
            $table->decimal('tier_base_amount', 10, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('supervisor_comission_orders', function (Blueprint $table) {
        $table->dropColumn('tier');
        $table->dropColumn('tier_base_amount');
    });
    }
};
