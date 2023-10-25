<?php
namespace App\Actions;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UpdateClient {

  public function handle(Request $request, Client $client) {

    DB::transaction(function() use ($request, $client) {

      if( !$client )
      {
          throw new \Exception('Not not updated');
      }
      
      $clientData = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'address' => $request->address,
        'city' => $request->city,
        'state' => $request->state,
        'zip' => $request->zip,
        'user_id' => auth()->user()->id
      ];

      $client->update($clientData);
    });
  }
}
