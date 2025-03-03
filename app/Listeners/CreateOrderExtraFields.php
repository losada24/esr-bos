<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Traits\Snapshot;

class CreateOrderExtraFields
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        // dd($event->order->installationTeams);
        if ($event->order->installationTeams()->count() > 0 && $event->order->paymentExtraFields()->count() === 0) {
            foreach ($event->order->installationTeams as $team) {
                $order = $event->order;
                $order->paymentExtraFields()->create([
                    'installation_team_id' => $team->user_id,
                ]);
            }
        }
    }
}
