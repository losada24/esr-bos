<?php

namespace App\Console\Commands;

use App\Enum\OrderStatusEnum;
use App\Models\Order;
use App\Models\OrderPhase;
use App\Support\Orders\OrderPhaseManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ChangeOrdersToExecutionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-orders-to-execution-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change orders to execution status.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
      $today = Carbon::now();
      $orders = Order::where(function($query) {
          $query->where('status', OrderStatusEnum::CONFIRMED->value)
                ->orWhere('status', OrderStatusEnum::RESCHEDULE->value);
        })
        ->where('install_by_phases', false)
        ->whereDate('installation_date', $today)
        ->get();

      foreach ($orders as $order) {
          $order->status = OrderStatusEnum::EXECUTION->value;
          $order->save();
          $order->touch();

          $order->orderStatus()->create([
            'status' => $order->status,
            'user_id' => 1,
            'notes' => $order->status . " created by command",
            'start_date' => $order->installation_date,
            'end_date' => $order->installation_end_date,
            'pickup_date' => $order->delivery_date,
            'inspection_date' => $order->inspection_date,
            'finish_date' => $order->finish_date,
            'final_inspection_date' => $order->final_inspection_date,
            'complete_date' => $order->complete_date,
          ]);
      }

      $phaseManager = app(OrderPhaseManager::class);
      $phases = OrderPhase::with('order')
        ->whereHas('order', fn ($query) => $query->where('install_by_phases', true))
        ->where(function($query) {
          $query->where('status', OrderStatusEnum::CONFIRMED->value)
            ->orWhere('status', OrderStatusEnum::RESCHEDULE->value);
        })
        ->whereDate('installation_date', $today)
        ->get();

      foreach ($phases as $phase) {
        $before = $this->phaseSnapshot($phase);
        $order = $phase->order;
        $previousOrderStatus = $order?->status;

        $phase->status = OrderStatusEnum::EXECUTION->value;
        $phase->save();
        $phase->touch();

        $phase->logs()->create([
          'order_id' => $phase->order_id,
          'user_id' => 1,
          'action' => 'auto_status_update',
          'status' => $phase->status,
          'before' => $before,
          'after' => $this->phaseSnapshot($phase),
          'notes' => $phase->status . " created by command",
        ]);

        if ($order) {
          $phaseManager->syncOrderSummary($order);
          $order->refresh();

          if ($previousOrderStatus !== $order->status) {
            $order->orderStatus()->create([
              'status' => $order->status,
              'user_id' => 1,
              'notes' => $order->status . " created by phase command",
              'start_date' => $order->installation_date,
              'end_date' => $order->installation_end_date,
              'pickup_date' => $order->delivery_date,
              'inspection_date' => $order->inspection_date,
              'finish_date' => $order->finish_date,
              'final_inspection_date' => $order->final_inspection_date,
              'complete_date' => $order->complete_date,
            ]);
          }
        }
      }
    }

    private function phaseSnapshot(OrderPhase $phase): array
    {
      return [
        'status' => $phase->status,
        'delivery_date' => $phase->delivery_date ? Carbon::parse($phase->delivery_date)->format('Y-m-d') : null,
        'installation_date' => $phase->installation_date ? Carbon::parse($phase->installation_date)->format('Y-m-d') : null,
        'installation_end_date' => $phase->installation_end_date ? Carbon::parse($phase->installation_end_date)->format('Y-m-d') : null,
      ];
    }
}
