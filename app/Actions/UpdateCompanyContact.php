<?php
namespace App\Actions;

use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Models\CompanyContact;
use App\Models\Referral;

class UpdateCompanyContact {

  public function handle(Request $request, CompanyContact $companyContact) {

    if ($request->has('clients') && is_array($request->input('clients'))) {
        $clientIdsFromRequest = collect($request->input('clients', []))
                ->pluck('id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->toArray();

        $removedClientsQuery = Client::where('company_contact_id', $companyContact->id);
        if (!empty($clientIdsFromRequest)) {
            $removedClientsQuery->whereNotIn('id', $clientIdsFromRequest);
        }

        $removedClients = $removedClientsQuery->get();

        foreach ($removedClients as $client) {
            $order = $client->orders()->select('id', 'name', 'order_number')->first();

            if (!$order) {
                $commercialLink = $client->orderCompanyContacts()->with(['order:id,name,order_number'])->first();
                $order = $commercialLink?->order;
            }

            if ($order) {
                $orderLabel = $order->name ?: ($order->order_number ? "Order #{$order->order_number}" : "Order {$order->id}");
                return [
                    'error' => "The client {$client->name} cannot be unlinked because they are associated with order {$orderLabel}."
                ];
            }
        }
    }

    DB::transaction(function() use ($request, $companyContact) {

      if( !$companyContact )
      {
          throw new \Exception('Not not updated');
      }
        //dd($request->clients);
     /* $company_id = auth()->user()->company_id;
      if (auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER)) {
        $company_id = $request->company_id;
      }*/
      //dd($request->clients);
     
      $companytData = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'website' => $request->website,
        'billing_street' => $request->billing_street,
        'billing_city' => $request->billing_city,
        'billing_state' => $request->billing_state,
        'billing_code' => $request->billing_code,
        'bid_due_date' => $request->bid_due_date,
        
        //'company_id' => $company_id,
      ];

    $companyContact->update($companytData);

    if ($request->has('clients') && is_array($request->input('clients'))) {
        $clientIdsFromRequest = collect($request->input('clients', []))
                ->pluck('id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->toArray();

        $removedClientsQuery = Client::where('company_contact_id', $companyContact->id);
        if (!empty($clientIdsFromRequest)) {
            $removedClientsQuery->whereNotIn('id', $clientIdsFromRequest);
        }

        $removedClientsQuery->update(['company_contact_id' => null]);

        // 4. Recorrer los clientes del request
        foreach ($request->input('clients', []) as $clientData) {
       
            $clientData['company_contact_id'] = $companyContact->id;
            $clientData['user_id'] = auth()->id(); // por si querés registrar al usuario

            if (!empty($clientData['id'])) {
                // Actualizar cliente existente
                $client = Client::find($clientData['id']);
                if ($client) {
                    $client->update($clientData);
                }
            } else {
                   $referral = null;

                  if ($clientData['source'] == ContactSourceEnum::EXTERNAL_REFERAL->value || 
                      $clientData['source'] == ContactSourceEnum::INTERNAL_REFERAL->value
                  ) {
                      $referral = Referral::firstOrCreate(
                      [
                          'name' => $clientData['refer_name'],
                          'phone' => $clientData['refer_phone'],
                          'type' => $clientData['source'],
                      ]
                      );
                    }

                //$companyContact->clients()->create($clientData);
                $client = Client::create([
                    'company_contact_id' => $companyContact->id,
                    'name' => $clientData['name'],
                    'phone' => $clientData['phone'],
                    'email' => $clientData['email'],
                    'user_id' => auth()->user()->id,
                    'vip_clients' => $clientData['vip_clients'] ?? false,
                    'vip_notes' => $clientData['vip_notes'] ?? null,
                    'contact_type' => ContactTypeEnum::COMMERCIAL_CONTACT->value,
                    'other_phone' => $clientData['other_phone'] ?? null,
                    'secondary_email' => $clientData['secondary_email'] ?? null,
                    'source' => $clientData['source'] ?? null,
                    'referral_id' => $referral?->id, // null si no aplica
                ]);
                //dd( $client);
            }
        }
    }

});
     
    
  }
}
