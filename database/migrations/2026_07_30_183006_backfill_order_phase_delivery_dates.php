<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE order_phases
            INNER JOIN orders ON orders.id = order_phases.order_id
            SET order_phases.delivery_date = orders.delivery_date
            WHERE order_phases.delivery_date IS NULL
              AND order_phases.position = 1
              AND orders.delivery_date IS NOT NULL
        ");
    }

    public function down(): void
    {
        //
    }
};
