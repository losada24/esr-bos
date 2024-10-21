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
            $table->decimal('project_amount', 10, 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('type_of_financing', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('project_amount');
            $table->dropColumn('city');
            $table->dropColumn('type_of_financing');
        });
    }
};
