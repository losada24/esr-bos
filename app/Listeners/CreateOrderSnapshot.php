<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Traits\Snapshot;

class CreateOrderSnapshot
{

    use Snapshot;
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $this->createSnapshot($event->order);
    }
}
