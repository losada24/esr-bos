<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->string('service_type')->nullable()->change();
            $table->string('service_status')->nullable()->change();
            $table->string('priority')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->string('service_type')->nullable(false)->change();
            $table->string('service_status')->nullable(false)->change();
            $table->string('priority')->nullable(false)->change();
        });
    }
};
