<?php
namespace App\Actions;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ClientAddress;
use App\Traits\Bigin;

class CreateClient {

  use Bigin;
  
  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $existingClient = Client::where('phone', $request->phone)->first();

      if( !$existingClient )
      {
          $existingClient = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'user_id' => auth()->user()->id,
          ]);

          $tag = new \stdClass();
          $tag->name = 'New Client';
          
          $this->createContact([
            'Owner' => config('bigin.bigin_owner_id'),
            'Notify_Owner' => true,
            'Last_Name' => $request->name,
            'Email' => $request->email,
            'Mobile' => $request->phone,
            'Description' => $request->notes . ' - Appointment:' . $request->appointment_date,
            'Tag' => [
              $tag
            ]
          ]);
      }


      if ($request->address != null) {
        $existingAddress = ClientAddress::where('address', $request->address)->where('client_id', $existingClient->id)->first();
  
        if( !$existingAddress )
        {
          $existingClient->clientAddress()->save(new ClientAddress([
            'address' => $request->address,
            'appointment_date' => $request->appointment_date,
            'notes' => $request->notes,
          ]));
        }
      }

      if( !$existingClient )
      {
          throw new \Exception('Client not created');
      }

    });
  }
}
