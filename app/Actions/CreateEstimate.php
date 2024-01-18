<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;

class CreateEstimate {

  public function handle(Request $request) {
    
      DB::beginTransaction();

      try {

        $estimateStatus = OrderStatusEnum::$ESTIMATE;
        if (auth()->user()->hasRole(RoleEnum::$SUB_DEALER)) {
          $estimateStatus = OrderStatusEnum::$SUB_DEALER_ESTIMATE;
        }

        $estimate = Order::create([
          'name' => $request->name,
          'project_name' => $request->project_name,
          'client_id' => $request->client_id,
          'frame_color' => $request->frame_color,
          'glass_color' => $request->glass_color,
          'glass_type' => $request->glass_type,
          'markup' => $request->markup,
          'notes' => $request->notes,
          'user_id' => auth()->user()->id,
          'company_id' => auth()->user()->company_id,
          'status' => $estimateStatus,
          'tax_rate' => $request->tax_rate,
          'installation' => $request->installation,
          'permit' => $request->permit,
          'other' => $request->other,
          'external_purchase_id' => $request->external_purchase_id,
          'company_markup' => auth()->user()->company->markup,
          'company_promotion' => auth()->user()->company->promotion,
          'user_markup' => auth()->user()->markup,
        ]);
        
        if( !$estimate )
        {
            throw new \Exception('Estimate not created');
        }

        $estimate->orderStatus()->create([
          'status' => $estimateStatus,
          'notes' => "$estimateStatus created by " . auth()->user()->name
        ]);

        DB::commit();
        return $estimate;
        
      } catch (\Exception $e) {
        DB::rollback();
        throw $e;
      }
  }
}
