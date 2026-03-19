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
        Schema::table('referrals', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->foreignId('client_id')->nullable()->after('type')->constrained('clients')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('client_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['client_id']);
            $table->dropColumn(['email', 'client_id', 'user_id']);
        });
    }
};
