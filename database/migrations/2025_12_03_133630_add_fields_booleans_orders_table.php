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
        $table->boolean('name_check')->nullable()->default(false);
        $table->boolean('address_check')->nullable()->default(false);
        $table->boolean('amount_check')->nullable()->default(false);
        $table->boolean('email_check')->nullable()->default(false);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('name_check');
        $table->dropColumn('address_check');
        $table->dropColumn('amount_check');
        $table->dropColumn('email_check');
    });
    }
};
