<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrderStatus;

class UpdateOrderStatusNote {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $orderStatus = OrderStatus::find($request->id);
      if( !$orderStatus )
      {
        throw new \Exception('Not not updated');
      }
      
      $orderStatus->update(['notes' => $request->notes]);
    });
  }
}
