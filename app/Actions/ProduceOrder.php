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
      
      if( !$order )
      {
          throw new \Exception('Not not updated');
      }

      $orderData = [
        'status' => $request->status,
        'notes' => $request->notes,
      ];

      $order->update($orderData);

    });
  }
}
