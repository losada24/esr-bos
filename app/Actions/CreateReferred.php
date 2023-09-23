<?php
namespace App\Actions;

use App\Models\ReferralsStatusUpdates;
use App\Models\Referred;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enum\ReferredStatusEnum;

class CreateReferred {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {
      $referred = Referred::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'notes' => $request->notes,
        'user_id' => $request->user_id,
      ]);
      
      if( !$referred )
      {
          throw new \Exception('Referred not created');
      }

      $referralsStatusUpdate = new ReferralsStatusUpdates([
        'status' => ReferredStatusEnum::$NEW,
        'notes' => "The referred was created with status: " . ReferredStatusEnum::$NEW . ".",
        'user_id' => $request->user_id
      ]);

      $referred->referralsStatusUpdate()->save($referralsStatusUpdate);
    });
  }
}
