<?php
namespace App\Actions;

use App\Models\ReferralsStatusUpdates;
use App\Models\Referred;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateReferred {

  public function handle(Request $request, Referred $referred) {

    DB::transaction(function() use ($request, $referred) {

      if( !$referred )
      {
          throw new \Exception('Order not updated');
      }
      
      $referredData = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'notes' => $request->notes,
        'status' => $request->status,
      ];

      if ($request->status != $referred->status) {
          $referralsStatusUpdate = new ReferralsStatusUpdates([
            'status' => $request->status,
            'notes' => $request->status_notes,
            'user_id' => Auth::id(),
          ]);

          $referred->referralsStatusUpdate()->save($referralsStatusUpdate);
      }

      $referred->update($referredData);
    });
  }
}
