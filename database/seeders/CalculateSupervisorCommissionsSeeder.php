<?php

namespace Database\Seeders;

use App\Enum\ServiceEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalculateSupervisorCommissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      // Obtén las órdenes con los campos necesarios
        $orders = DB::table('orders')
            ->select('id', 'project_amount', 'supervisor_payment_percentage', 'service')
            ->whereIn('service', [
                ServiceEnum::INSTALLATION->value,
                ServiceEnum::INSTALLATION_ONLY->value
            ])
            ->get();

        // Itera sobre cada orden para calcular las comisiones
        foreach ($orders as $order) {
            // Convierte valores a flotantes para evitar problemas de precisión
            $projectAmount = (float) $order->project_amount;
            $percentage = (float) $order->supervisor_payment_percentage / 100; // Convierte porcentaje a decimal

            // Calcula la comisión
            $commission = $projectAmount * $percentage;

            // Actualiza el campo supervisor_commissions en la tabla orders
            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'supervisor_commissions' => $commission,
                ]);
        }
    }
}
