<?php

namespace Database\Seeders;

use App\Enum\ServiceEnum;
use App\Models\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateSupervisorComission extends Seeder
{
    use \App\Traits\ComissionSupervisor;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::where('service', ServiceEnum::INSTALLATION->value)
          ->where('project_amount', '>', 0)
          ->where('supervisor_commissions', 0)
          ->get();

          DB::beginTransaction();
          try {
              foreach ($orders as $order) {
                  $comissions = $this->ComissionSupervisor($order->project_amount);
                  $totalCommission = array_sum(array_column($comissions, 'amount'));
                  $order->supervisor_commissions = $totalCommission;
                  $order->save();
              }
              DB::commit();
          } catch (\Exception $e) {
              DB::rollBack();
              throw $e;
          }
    }
}
