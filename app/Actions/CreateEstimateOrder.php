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
        'phone_number' => $request->phone_number,
        'street_address' => $request->street_address,
        'city' => $request->city,
        'state' => $request->state,
        'zip_code' => $request->zip_code,
        'country' => $request->country,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'email' => $request->email,
        'notes' => $request->notes,
        'amount' => $request->amount,
        'user_id' => auth()->user()->id,
      ];

      $estimate->payments()->create($payment);

      $estimateData = [
        'status' => OrderStatusEnum::$ACCOUNTING,
      ];

      $estimate->update($estimateData);

    });
  }
}
