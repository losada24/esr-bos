<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Traits\Snapshot;
use Illuminate\Support\Facades\DB;

class CreateOrderSnapshot
{

    use Snapshot;
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        DB::afterCommit(function () use ($event) {
            $order = $event->order->fresh();
            if (!$order) {
                return;
            }
            $this->createSnapshot($order);
        });
    }
}
