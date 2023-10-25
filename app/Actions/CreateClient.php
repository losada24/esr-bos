<?php
namespace App\Actions;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreateClient {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {
      $client = Client::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'address' => $request->address,
        'city' => $request->city,
        'state' => $request->state,
        'zip' => $request->zip,
        'user_id' => auth()->user()->id
      ]);

      if( !$client )
      {
          throw new \Exception('Client not created');
      }

    });
  }
}
