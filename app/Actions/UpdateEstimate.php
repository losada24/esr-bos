<?php
namespace App\Actions;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateEstimate {

  public function handle(Request $request, Order $estimate) {

    DB::transaction(function() use ($request, $estimate) {

      if( !$estimate )
      {
          throw new \Exception('Not not updated');
      }
      
      $orderData = [
        'name' => $request->name,
        'project_name' => $request->project_name,
        'client_id' => $request->client_id,
        'frame_color' => $request->frame_color,
        'glass_color' => $request->glass_color,
        'markup' => $request->markup,
        'notes' => $request->notes,
        'user_id' => auth()->user()->id,
        'tax_rate' => $request->tax_rate,
        'installation' => $request->installation,
        'permit' => $request->permit,
        'other' => $request->other
      ];

      $estimate->update($orderData);
    });
  }
}
