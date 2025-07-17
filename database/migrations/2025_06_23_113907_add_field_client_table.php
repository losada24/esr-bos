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
      Schema::table('clients', function (Blueprint $table) {
        $table->string('contact_type')->nullable();
        $table->string('other_phone')->nullable();
        $table->string('secondary_email')->nullable();
        $table->string('source')->nullable();
       
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('clients', function (Blueprint $table) {
        $table->dropColumn('contact_type');
        $table->dropColumn('other_phone');
        $table->dropColumn('secondary_email');
        $table->dropColumn('source');
    
    });
    }
};
