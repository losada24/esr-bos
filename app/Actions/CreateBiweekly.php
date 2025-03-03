<?php
namespace App\Actions;

use App\Models\Attachment;
use App\Models\Biweekly;
use App\Models\Company;
use App\Models\InstallationTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreateBiweekly {

  public function handle(Request $request) {
      //dd($request);
    
    DB::transaction(function() use ($request) {
      
      $biweekly = Biweekly::create([
        'start_biweekly_period' => $request->period[0],
        'end_biweekly_period' => $request->period[1],
        'payment_method' => $request->payment_method,
        'installation_team_id' => $request->installation_team_id,
      
      ]);

      //dd($biweekly);
      
      if( !$biweekly )
      {
          throw new \Exception('Installation team not created');
      }

    });
  }
}
