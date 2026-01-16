<?php

namespace App\Traits;

use App\Models\Order;

trait Snapshot {

    public function createSnapshot(Order $order): void
    {
        //$order->loadMissing('products.notes');
        $order->loadMissing([
            'notes' => function ($query) {
                $query->with('user');
            },
            'tags' => function ($query) {
                $query->with('user');
            },
            'attachments' => function ($query) {
                $query->with('user');
            },
            'owners',
            'client',
            'typeOfWork',
            'typeOfHousing',
            'supervisor',
            'travelCost',
            'durationOfWork',
            'user',
            'saleForm',
        ]);

        $actor = auth()->user();
        $snapshotData = $order->toArray();
        $snapshotData['actor'] = $actor ? [
            'id' => $actor->id,
            'name' => $actor->name,
            'email' => $actor->email,
        ] : null;
        $snapshotData['event_type'] = $order->wasRecentlyCreated ? 'order_created' : 'order_updated';

        $order->snapshots()->create([
          'user_id' => $actor ? $actor->id : $order->user_id,
          'order_id' => $order->id,
          'status' => $order->status,
          'snapshot_data' => $snapshotData
      ]);
    }
}
