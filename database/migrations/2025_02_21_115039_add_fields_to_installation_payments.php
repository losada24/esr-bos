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
      Schema::table('payment_extra_fields', function (Blueprint $table) {
        $table->dropColumn('documents_submitted');
        $table->dropColumn('collected_payment');
        $table->dropColumn('extra_work');
        $table->dropColumn('extra_discount');
        $table->dropColumn('other_cost_installer');
      });
        Schema::table('installation_payments', function (Blueprint $table) {
          $table->decimal('extra_work', 10, 2)->nullable();
          $table->decimal('extra_discount', 10, 2)->nullable();
          $table->decimal('other_cost_installer', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
          Schema::table('payment_extra_fields', function (Blueprint $table) {
          $table->decimal('extra_work', 10, 2)->nullable();
          $table->decimal('extra_discount', 10, 2)->nullable();
          $table->decimal('other_cost_installer', 10, 2)->nullable();
          $table->string('documents_submitted')->nullable();
          $table->boolean('collected_payment')->nullable()->default(false);
        });
        Schema::table('installation_payments', function (Blueprint $table) {
          $table->dropColumn('extra_work');
          $table->dropColumn('extra_discount');
          $table->dropColumn('other_cost_installer');
        });
    }
};
