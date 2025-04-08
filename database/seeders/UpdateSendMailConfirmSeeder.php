<?php

namespace Database\Seeders;

use App\Enum\OrderStatusEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateSendMailConfirmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('orders')
      // Filtra solo los registros donde service sea INSTALLATION o INSTALLATION_ONLY
      ->whereIn('status', [
          OrderStatusEnum::CONFIRMED->value,
      ])
      ->update([
          'is_send_email' => true,
      ]);
    }
}
