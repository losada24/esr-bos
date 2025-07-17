<?php
namespace App\Actions;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Models\Referral;

class UpdateClient {

  public function handle(Request $request, Client $client) {

    DB::transaction(function() use ($request, $client) {

      if( !$client )
      {
          throw new \Exception('Not not updated');
      }

     /* $company_id = auth()->user()->company_id;
      if (auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER)) {
        $company_id = $request->company_id;
      }*/
      //dd($request); 

      
      
      $clientData = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'contact_type' => $request->contact_type,
        'other_phone' => $request->other_phone,
        'secondary_email' => $request->secondary_email,
        'source' => $request->source,
        'address' => $request->address,
        'city' => $request->city,
        'state' => $request->state,
        'zip' => $request->zip,
        'user_id' => auth()->user()->id,
        'vip_clients' => $request->vip_clients,
        'vip_notes' => $request->vip_notes,
        'company_contact_id' => $request->company_contact_id ? $request->company_contact_id : null,
        //'company_id' => $company_id,
      ];

      
    // Buscar o crear el referral si aplica
    if (in_array($request->source, ['EXTERNAL REFERAL', 'INTERNALREFERAL'])) {
      $referral = Referral::updateOrCreate(
          [
              'name' => $request->refer_name,
              'phone' => $request->refer_phone,
          ],
          [
              'type' => $request->source,
          ]
          );

        // Asociar el referral al cliente
        $clientData['referral_id'] = $referral->id;
    } else {
        // Si ya tenía uno, puedes quitarlo si no aplica más
        $clientData['referral_id'] = null;
    }
    $client->update($clientData);

    });
}

}
