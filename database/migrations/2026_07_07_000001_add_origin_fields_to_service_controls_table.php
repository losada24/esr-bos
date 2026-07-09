<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->string('service_source')->default('ESR')->after('is_bm');
            $table->string('creation_source')->default('MANUAL')->after('service_source');
            $table->index(['service_source', 'creation_source']);
        });
    }

    public function down(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->dropIndex(['service_source', 'creation_source']);
            $table->dropColumn(['service_source', 'creation_source']);
        });
    }
};
