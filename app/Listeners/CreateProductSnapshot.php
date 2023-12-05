<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Traits\Snapshot;

class CreateProductSnapshot
{
    use Snapshot;
    
    /**
     * Handle the event.
     */
    public function handle(ProductCreated $event): void
    {
        $this->createSnapshot($event->order);
    }
}
