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
        if (!Schema::hasTable('client_company_contacts')) {
            return;
        }

        Schema::table('client_company_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('client_company_contacts', 'deleted_by_user_id')) {
                $table->unsignedBigInteger('deleted_by_user_id')->nullable()->after('is_primary');
                $table->foreign('deleted_by_user_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('client_company_contacts') || !Schema::hasColumn('client_company_contacts', 'deleted_by_user_id')) {
            return;
        }

        Schema::table('client_company_contacts', function (Blueprint $table) {
            $table->dropForeign(['deleted_by_user_id']);
            $table->dropColumn('deleted_by_user_id');
        });
    }
};
