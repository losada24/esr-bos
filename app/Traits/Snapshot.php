<?php

namespace App\Traits;

use App\Models\Order;

trait Snapshot {

    public function createSnapshot(Order $order): void
    {
        $orderSnapshot = $order->snapshots()->create([
          'user_id' => $order->user_id,
          'order_id' => $order->id,
          'status' => $order->status,
          'order_details' => [
            'name' => $order->name,
            'project_name' => $order->project_name,
            'client_id' => $order->client_id,
            'frame_color' => $order->frame_color,
            'glass_color' => $order->glass_color,
            'markup' => $order->markup,
            'notes' => $order->notes,
            'user_id' => auth()->user()->id,
            'company_id' => auth()->user()->company_id,
            'status' => $order->status,
            'tax_rate' => $order->tax_rate,
            'installation' => $order->installation,
            'permit' => $order->permit,
            'other' => $order->other,
            'products' => $order->products
          ]
      ]);
    }
}