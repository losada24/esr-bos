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
      Schema::table('order_status', function (Blueprint $table) {
        $table->date('inspection_date')->nullable();
        $table->date('finish_date')->nullable(); 
        $table->date('final_inspection_date')->nullable(); 
        $table->date('complete_date')->nullable(); 
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('order_status', function (Blueprint $table) {
        $table->dropColumn('inspection_date');
        $table->dropColumn('finish_date');
        $table->dropColumn('final_inspection_date');
        $table->dropColumn('complete_date');
    });
    }
};
