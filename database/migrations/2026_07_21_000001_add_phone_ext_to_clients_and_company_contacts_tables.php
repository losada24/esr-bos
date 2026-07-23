<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('phone_ext', 20)->nullable()->after('phone');
        });

        Schema::table('company_contacts', function (Blueprint $table) {
            $table->string('phone_ext', 20)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('company_contacts', function (Blueprint $table) {
            $table->dropColumn('phone_ext');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('phone_ext');
        });
    }
};
