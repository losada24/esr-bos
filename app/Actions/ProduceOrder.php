<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Enum\OrderStatusEnum;

class ProduceOrder {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $order = Order::find($request->id);
      $orderStatus = [
        'status' => $request->status,
        'notes' => $request->notes
      ];
      
      if( !$order )
      {
          throw new \Exception('Not not updated');
      }

      $orderData = [
        'status' => $request->status
      ];

      $order->update($orderData);
      $order->orderStatus()->create($orderStatus);

    });
  }
}
