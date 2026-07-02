<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderStageOverdue;
use App\Support\OrderStageOverdueTracker;
use Illuminate\Console\Command;

class SyncOrderStageOverdues extends Command
{
    protected $signature = 'app:sync-order-stage-overdues';

    protected $description = 'Sync active overdue stage history for ESR process orders.';

    public function handle(OrderStageOverdueTracker $tracker): int
    {
        $count = 0;

        Order::query()
            ->whereIn('status', array_keys($tracker->stageBusinessDayLimits()))
            ->with('orderStatus')
            ->chunkById(200, function ($orders) use ($tracker, &$count): void {
                foreach ($orders as $order) {
                    $tracker->sync($order);
                    $count++;
                }
            });

        OrderStageOverdue::query()
            ->where('is_active', true)
            ->whereNull('resolved_at')
            ->whereHas('order', function ($query): void {
                $query->whereColumn('orders.status', '!=', 'order_stage_overdues.status');
            })
            ->get()
            ->each(function (OrderStageOverdue $overdue) use ($tracker): void {
                $tracker->resolveActiveForOrderStatus(
                    (int) $overdue->order_id,
                    (string) $overdue->status,
                    $overdue->stage_started_at
                );
            });

        $this->info("Synced {$count} orders for overdue stage history.");

        return self::SUCCESS;
    }
}
