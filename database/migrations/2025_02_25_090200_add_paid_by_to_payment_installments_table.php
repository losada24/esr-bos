<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_installments', function (Blueprint $table) {
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_installments', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropColumn('paid_by');
        });
    }
};
