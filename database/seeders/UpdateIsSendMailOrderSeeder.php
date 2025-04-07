<?php

namespace Database\Seeders;

use App\Enum\OrderStatusEnum;
use App\Models\OrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateIsSendMailOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('orders')
      // Filtra solo los registros donde service sea INSTALLATION o INSTALLATION_ONLY
      ->whereIn('status', [
          OrderStatusEnum::PLANNED->value,
      ])
      ->update([
          'is_send_email' => true,
      ]);
    }
}
