<?php

namespace Database\Seeders;

use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateSupervisorPaymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('orders')
            // Filtra solo los registros donde service sea INSTALLATION o INSTALLATION_ONLY
            ->whereIn('service', [
                ServiceEnum::INSTALLATION->value,
                ServiceEnum::INSTALLATION_ONLY->value
            ])
            ->update([
                'supervisor_payment_status' => SupervisorPaymentStatusEnum::OPEN->value,
                'supervisor_payment_percentage' => 0.30,
            ]);
    }
}
