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
        Schema::table('installation_teams', function (Blueprint $table) {
            $table->boolean('disable_expiration_document_emails')->default(false)->after('annual_w9_expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installation_teams', function (Blueprint $table) {
            $table->dropColumn('disable_expiration_document_emails');
        });
    }
};
