<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderStatusInitialLoadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();

        $orders->each(function ($order) {
          $order->orderStatus()->create([
            'status' => $order->status,
            'notes' => $order->status . " created by " . $order->user->name,
            'user_id' => $order->user->id
          ]);
        });
    }
}
