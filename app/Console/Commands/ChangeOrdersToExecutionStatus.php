<?php

namespace App\Console\Commands;

use App\Enum\OrderStatusEnum;
use App\Models\Order;
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
        ->whereDate('installation_date', $today)
        ->get();

      foreach ($orders as $order) {
          $order->status = OrderStatusEnum::EXECUTION->value;
          $order->save();

          $order->orderStatus()->create([
            'status' => $order->status,
            'user_id' => auth()->user()->id,
            'notes' => $order->status." created by " . auth()->user()->name,
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
