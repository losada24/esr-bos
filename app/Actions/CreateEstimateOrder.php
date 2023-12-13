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

      $estimateData = [
        'status' => OrderStatusEnum::$ACCOUNTING,
      ];

      $estimate->update($estimateData);

    });
  }
}
