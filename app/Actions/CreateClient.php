<?php
namespace App\Actions;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;

class CreateClient {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $company_id = auth()->user()->company_id;
      if (auth()->user()->hasRole(RoleEnum::$ADMIN)) {
        $company_id = $request->company_id;
      }

      $client = Client::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'address' => $request->address,
        'city' => $request->city,
        'state' => $request->state,
        'zip' => $request->zip,
        'user_id' => auth()->user()->id,
        'company_id' => $company_id,
      ]);

      if( !$client )
      {
          throw new \Exception('Client not created');
      }

    });
  }
}
