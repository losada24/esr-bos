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
        Schema::table('users', function (Blueprint $table) {
          $table->renameColumn('mockup', 'markup');
        });

        Schema::table('companies', function (Blueprint $table) {
          $table->renameColumn('mockup', 'markup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
          $table->renameColumn('markup', 'mockup');
        });

        Schema::table('companies', function (Blueprint $table) {
          $table->renameColumn('markup', 'mockup');
        });
    }
};
