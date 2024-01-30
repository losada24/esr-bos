<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Enum\OrderStatusEnum;

class CreateEstimateOrder {

  public function handle(Request $request, Order $estimate) {
    
    DB::transaction(function() use ($request, $estimate) {

      if( !$estimate )
      {
          throw new \Exception('Not not updated');
      }

      $payment = [
        'method' => $request->method,
        'street_address' => $request->street_address,
        'city' => $request->city,
        'state' => $request->state,
        'zip_code' => $request->zip_code,
        'country' => $request->country,
        'notes' => $request->notes,
        'amount' => $request->amount,
        'user_id' => auth()->user()->id,
      ];

      $estimate->payments()->create($payment);

      $estimateData = [
        'status' => OrderStatusEnum::$ACCOUNTING,
      ];

      $estimate->update($estimateData);
      $estimate->orderStatus()->create([
        'status' => OrderStatusEnum::$ACCOUNTING,
        'user_id' => auth()->user()->id,
        'notes' => "Payment was submitted by " . auth()->user()->name . " using " . $request->method
      ]);

    });
  }
}
