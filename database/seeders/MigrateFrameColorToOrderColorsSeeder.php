<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateFrameColorToOrderColorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $orders = DB::table('orders')
      ->whereNotNull('frame_color')
      ->get();

        foreach ($orders as $order) {
            DB::table('order_colors')->insert([
                'order_id'   => $order->id,
                'name'       => strtoupper(trim($order->frame_color)), // opcional: asegurar formato
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
  }
}
