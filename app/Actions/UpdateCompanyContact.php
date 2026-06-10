<?php
namespace App\Actions;

use App\Enum\ContactTypeEnum;
use App\Models\Client;
use App\Models\CompanyContact;
use App\Support\ClientCompanyContactManager;
use App\Support\ReferralResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateCompanyContact {

  public function __construct(
    private readonly ReferralResolver $referralResolver,
    private readonly ClientCompanyContactManager $clientCompanyContactManager
  ) {}

  public function handle(Request $request, CompanyContact $companyContact) {

    if ($request->has('clients') && is_array($request->input('clients'))) {
        $clientIdsFromRequest = collect($request->input('clients', []))
                ->pluck('id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->toArray();

        $removedClientsQuery = $companyContact->clients();
        if (!empty($clientIdsFromRequest)) {
            $removedClientsQuery->whereNotIn('clients.id', $clientIdsFromRequest);
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

        $removedClientsQuery = $companyContact->clients();
        if (!empty($clientIdsFromRequest)) {
            $removedClientsQuery->whereNotIn('clients.id', $clientIdsFromRequest);
        }

        $removedClientsQuery->get()->each(function (Client $client) use ($companyContact) {
            $this->clientCompanyContactManager->detach($client, $companyContact->id);
        });

        // 4. Recorrer los clientes del request
        foreach ($request->input('clients', []) as $clientData) {
       
            $clientData['user_id'] = auth()->id(); // por si querés registrar al usuario

            if (!empty($clientData['id'])) {
                // Actualizar cliente existente
                $client = Client::find($clientData['id']);
                if ($client) {
                    $referral = $this->referralResolver->resolve($clientData);
                    $clientData['referral_id'] = $referral?->id;
                    if (!empty($client->company_contact_id)) {
                        unset($clientData['company_contact_id']);
                    } else {
                        $clientData['company_contact_id'] = $companyContact->id;
                    }
                    $client->update($clientData);
                    $this->clientCompanyContactManager->attach($client, $companyContact->id);
                }
            } else {
                $referral = $this->referralResolver->resolve($clientData);

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
                $this->clientCompanyContactManager->sync(
                    $client,
                    [$companyContact->id],
                    $companyContact->id
                );
                //dd( $client);
            }
        }
    }

});
     
    
  }
}
