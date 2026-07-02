<?php

namespace App\Observers;

use App\Models\Order;
use App\Support\OrderStageOverdueTracker;

class OrderObserver
{
    public function updating(Order $order): void
    {
        if (!$order->isDirty('status')) {
            return;
        }

        app(OrderStageOverdueTracker::class)->resolveActiveForOrderStatus(
            (int) $order->id,
            (string) $order->getOriginal('status')
        );
    }
}
