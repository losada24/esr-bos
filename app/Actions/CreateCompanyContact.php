<?php
namespace App\Actions;

use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ClientAddress;
use App\Models\CompanyContact;
use App\Models\Referral;
use App\Traits\Bigin;

class CreateCompanyContact {

  use Bigin;
  
  public function handle(Request $request) {
    
    return DB::transaction(function() use ($request) {

      $existingCompany = CompanyContact::where('name', $request->name)->first();
       
      if( !$existingCompany )
      {
       
          $existingCompany = CompanyContact::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'website' => $request->website,
            'billing_street' => $request->billing_street,
            'billing_city' => $request->billing_city,
            'billing_state' => $request->billing_state,
            'billing_code' => $request->billing_code,
            'bid_due_date' => $request->bid_due_date,
            'user_id' => auth()->user()->id,
          ]);
      }
      if (!empty($request->clients)) {
        foreach ($request->clients as $client) {
              $referral = null;

            if ($client['source'] == ContactSourceEnum::EXTERNAL_REFERAL->value || 
                $client['source'] == ContactSourceEnum::INTERNAL_REFERAL->value
            ) {
                $referral = Referral::firstOrCreate(
                [
                    'name' => $client['refer_name'],
                    'phone' => $client['refer_phone'],
                    'type' => $client['source'],
                ]
                );
              }
          Client::create([
            'company_contact_id' => $existingCompany->id,
            'name' =>$client['name'],
            'phone' => $client['phone'],
            'email' => $client['email'],
            'user_id' => auth()->user()->id,
            'vip_clients' => $client['vip_clients'] ?? false,
            'vip_notes' => $client['vip_notes'] ?? null,
            'contact_type' => ContactTypeEnum::COMMERCIAL_CONTACT->value,
            'other_phone' => $client['other_phone'] ?? null,
            'secondary_email' => $client['secondary_email'] ?? null,
            'source' => $client['source'] ?? null,
            'referral_id' => $referral?->id, // null si no aplica
          ]);

        }
      } 

      if( !$existingCompany )
      {
          throw new \Exception('Company not created');
      }

      return $existingCompany;

    });
  }
}
