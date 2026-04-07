<?php
namespace App\Actions;

use App\Enum\OrderTypeEnum;
use App\Models\Client;
use App\Support\ClientCompanyContactManager;
use App\Traits\Bigin;
use App\Support\ReferralResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateClient {

  use Bigin;

  public function __construct(
    private readonly ReferralResolver $referralResolver,
    private readonly ClientCompanyContactManager $clientCompanyContactManager
  ) {}
  
  public function handle(Request $request) {
    
    return DB::transaction(function() use ($request) {

      $phone = is_string($request->phone) ? trim($request->phone) : null;
      $email = is_string($request->email) ? trim($request->email) : null;
      $forceCreate = $request->boolean('force_create');
      $isCommercial = $request->input('order_type') === OrderTypeEnum::COMMERCIAL->value;
      $fromModal = $request->boolean('from_modal');

      $existingClient = null;
      if (!empty($phone)) {
        $existingClient = Client::where('phone', $phone)->first();
      } elseif (!empty($email)) {
        $existingClient = Client::where('email', $email)->first();
        if ($existingClient && $fromModal && $isCommercial && !$forceCreate) {
          throw ValidationException::withMessages([
            'email' => ['This email is already associated with a client.'],
            'email_exists' => ['1'],
          ]);
        }
        if ($existingClient && $forceCreate) {
          $existingClient = null;
        }
      }
        //dd($request);
      $createdNewClient = false;

      if( !$existingClient )
      {
          $referral = $this->referralResolver->resolve($request->all());

          $existingClient = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'contact_type' => $request->contact_type ? $request->contact_type : '',
            'other_phone' => $request->other_phone,
            'secondary_email' => $request->secondary_email,
            'source' => $request->source,
            'user_id' => auth()->user()->id,
            'referral_id' => $referral?->id, // null si no aplica
            'company_contact_id' => $request->company_contact_id ? $request->company_contact_id : null,
          ]);

          $createdNewClient = true;


          /*$tag = new \stdClass();
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
          ]);*/
      }


      /*if ($request->address != null) {
        $existingAddress = ClientAddress::where('address', $request->address)->where('client_id', $existingClient->id)->first();
  
        if( !$existingAddress )
        {
          $existingClient->clientAddress()->save(new ClientAddress([
            'address' => $request->address,
            'appointment_date' => $request->appointment_date,
            'notes' => $request->notes,
          ]));
        }
      }*/

      if( !$existingClient )
      {
          throw new \Exception('Client not created');
      }

      $requestedCompanyIds = $this->extractRequestedCompanyIds($request);
      if (!empty($requestedCompanyIds)) {
        if ($createdNewClient) {
          $this->clientCompanyContactManager->sync(
            $existingClient,
            $requestedCompanyIds,
            $request->filled('company_contact_id') ? (int) $request->input('company_contact_id') : null
          );
        } else {
          foreach ($requestedCompanyIds as $companyId) {
            $this->clientCompanyContactManager->attach(
              $existingClient,
              $companyId,
              empty($existingClient->company_contact_id)
            );
          }
        }
      }

      return $existingClient;

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
