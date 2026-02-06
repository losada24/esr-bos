<?php

namespace App\Http\Controllers;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateClient;
use App\Actions\CreateCompanyContact;
use App\Actions\UpdateClient;
use App\Actions\UpdateCompanyContact;
use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\StoreCompanyContactRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Requests\UpdateCompanyContactRequest;
use App\Models\ClientAddress;
use App\Models\CompanyContact;
use Barryvdh\DomPDF\Facade\Pdf;

class CompanyContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('CompanyContact/Index', [
          'company_contacts' => CompanyContact::with(['clients'])->filter($request->only(['text']))
            ->orderBy('updated_at', 'desc')
            ->paginate()
            ->withQueryString()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('CompanyContact/Create', [
          'contact_type' => [
            ContactTypeEnum::RESIDENTIAL_CONTACT->value,
            ContactTypeEnum::COMMERCIAL_CONTACT->value,
          ],
          'sources' => [
            ContactSourceEnum::TIK_TOK->value,
            ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::SIGNS->value,
            ContactSourceEnum::WALK_IN->value,
            ContactSourceEnum::ESW_REFER->value,
            ContactSourceEnum::ESR_REFER->value,
            ContactSourceEnum::YOUTUBE->value,
            ContactSourceEnum::NEW_ORDER ->value,
            ContactSourceEnum::GOOGLE_ADS->value,
            ContactSourceEnum::SAME_AS_ORDER->value,
            ContactSourceEnum::DIRECT_CALL->value,
          ],
        ] );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCompanyContactRequest $storeCompanyContactRequest, CreateCompanyContact $createCompanyContact)
    {
        $company = $createCompanyContact->handle($storeCompanyContactRequest);
        if ($storeCompanyContactRequest->boolean('from_modal')) {
            return response()->json(['company' => $company]);
        }
        return redirect()->route('company_contact.index')
          ->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(CompanyContact $companyContact)
    {    
      
        return Inertia::render('CompanyContact/Edit', [
          //'clients' => $client,
          'contact_type' => [
            ContactTypeEnum::RESIDENTIAL_CONTACT->value,
            ContactTypeEnum::COMMERCIAL_CONTACT->value,
          ],
          'sources' => [
            ContactSourceEnum::TIK_TOK->value,
            ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::SIGNS->value,
            ContactSourceEnum::WALK_IN->value,
            ContactSourceEnum::ESW_REFER->value,
            ContactSourceEnum::ESR_REFER->value,
            ContactSourceEnum::YOUTUBE->value,
            ContactSourceEnum::NEW_ORDER ->value,
            ContactSourceEnum::PICHY_BOYS->value,
            ContactSourceEnum::GOOGLE_ADS->value,
            ContactSourceEnum::SAME_AS_ORDER->value,
            ContactSourceEnum::DIRECT_CALL->value,
          ],
          'companyContact' => $companyContact,
          'clientslist' => $companyContact->clients,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCompanyContactRequest $UpdateCompanyContactRequest, UpdateCompanyContact $updateCompanyContact, CompanyContact $companyContact)
    {
        $result = $updateCompanyContact->handle($UpdateCompanyContactRequest, $companyContact);
        if (is_array($result) && !empty($result['error'])) {
            return redirect()
                ->back()
                ->with('error', $result['error']);
        }
        if ($UpdateCompanyContactRequest->boolean('from_modal')) {
            return response()->json(['company' => $companyContact->fresh()]);
        }
        return redirect()->route('company_contact.index')
          ->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CompanyContact $companyContact)
    {
      // Suponiendo que tienes una relación definida: $companyContact->clients()
          if ($companyContact->clients()->exists()) {
              return redirect()
                  ->back()
                  ->with('error', 'The company cannot be deleted because it has associated clients');
          }

          $companyContact->delete();

          return redirect()
              ->back()
              ->with('success', 'Company deleted successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function isUnique($phone, $address = null)
    {
        $addressList = [];
        $clientsByPhone = Client::with(['clientAddress'])->where('phone', $phone)->get();    
        
        if ($address != null) {
          foreach ($clientsByPhone as $clientAddress) {
            foreach ($clientAddress->clientAddress as $client) {
              if (!in_array($client->address, $addressList) && $client->address != $address) {
                $addressList[] = $client->address;
              }
            }
          }
        }

        return response()->json(
          $addressList
        );
    }

    public function document($id)
    {
        $clientAddress = ClientAddress::with(['client', 'client.user'])->find($id);
        $pdf = Pdf::loadView('pdf.sale-form', ['clientAddress' => $clientAddress]);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('sale-form.pdf');
    }
}
