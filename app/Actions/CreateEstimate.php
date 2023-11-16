<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Enum\OrderStatusEnum;

class CreateEstimate {

  public function handle(Request $request) {
    
      DB::beginTransaction();

      try {
        $estimate = Order::create([
          'name' => $request->name,
          'project_name' => $request->project_name,
          'client_id' => $request->client_id,
          'frame_color' => $request->frame_color,
          'glass_color' => $request->glass_color,
          'markup' => $request->markup,
          'notes' => $request->notes,
          'user_id' => auth()->user()->id,
          'status' => OrderStatusEnum::$ESTIMATE,
          'tax_rate' => $request->tax_rate,
          'installation' => $request->installation,
          'permit' => $request->permit,
          'other' => $request->other
        ]);
        
        if( !$estimate )
        {
            throw new \Exception('Estimate not created');
        }

        DB::commit();
        return $estimate;
        
      } catch (\Exception $e) {
        DB::rollback();
        throw $e;
      }
  }
}
