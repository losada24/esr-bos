<?php
namespace App\Actions;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Models\Biweekly;

class UpdateBiweekly {

  public function handle(Request $request, Biweekly $biweekly) {

    DB::transaction(function() use ($request, $biweekly) {

      if( !$biweekly )
      {
          throw new \Exception('Not not updated');
      }
      
      $biweeklyData = [
        'start_biweekly_period' => $request->period[0],
        'end_biweekly_period' => $request->period[1],
        'payment_method' => $request->payment_method,
        'installation_team_id' => $request->installation_team_id,
      ];
      $biweekly->update($biweeklyData);
    });
  }
}
