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
      Schema::create('external_products_configurations', function (Blueprint $table) {
        $table->id();
        $table->string('external_product');
        $table->decimal('width', 10, 3);
        $table->decimal('height', 10, 3);
        $table->decimal('price', 10, 2);
        $table->json('extras')->nullable();
        $table->text('notes')->nullable();
        $table->softDeletes();
        $table->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::dropIfExists('external_products_configurations');
    }
};
