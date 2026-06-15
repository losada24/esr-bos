<?php
namespace App\Actions;

use App\Models\Client;
use App\Support\ClientCompanyContactManager;
use App\Support\ReferralResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateClient {

  public function __construct(
    private readonly ReferralResolver $referralResolver,
    private readonly ClientCompanyContactManager $clientCompanyContactManager
  ) {}

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
        'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : auth()->user()->id,
        'vip_clients' => $request->vip_clients,
        'vip_notes' => $request->vip_notes,
        'company_contact_id' => $request->company_contact_id ? $request->company_contact_id : null,
        //'company_id' => $company_id,
      ];

      
    $referral = $this->referralResolver->resolve($request->all());
    $clientData['referral_id'] = $referral?->id;
    $client->update($clientData);

    $requestedCompanyIds = $this->extractRequestedCompanyIds($request);
    if (!empty($requestedCompanyIds) || $request->has('company_contact_ids') || $request->has('company_contact_id')) {
      $this->clientCompanyContactManager->sync(
        $client,
        $requestedCompanyIds,
        $request->filled('company_contact_id') ? (int) $request->input('company_contact_id') : null
      );
    }

    });
}

  protected function extractRequestedCompanyIds(Request $request): array
  {
    $companyIds = [];

    if ($request->filled('company_contact_id')) {
      $companyIds[] = (int) $request->input('company_contact_id');
    }

    $requestCompanyIds = $request->input('company_contact_ids', []);
    if (is_array($requestCompanyIds)) {
      foreach ($requestCompanyIds as $companyId) {
        if (!empty($companyId)) {
          $companyIds[] = (int) $companyId;
        }
      }
    }

    return collect($companyIds)
      ->filter(fn ($companyId) => $companyId > 0)
      ->unique()
      ->values()
      ->all();
  }

}
