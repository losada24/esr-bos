<?php

namespace App\Http\Controllers;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateClient;
use App\Actions\CreateCompanyContact;
use App\Actions\UpdateClient;
use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\StoreCompanyContactRequest;
use App\Http\Requests\UpdateClientRequest;
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
        return Inertia::render('CompanyContact/Create', [] );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCompanyContactRequest $storeCompanyContactRequest, CreateCompanyContact $createCompanyContact)
    {
        $createCompanyContact->handle($storeCompanyContactRequest);
        return redirect()->route('company_contact.index')
          ->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {    
      
        return Inertia::render('Client/Edit', [
          //'clients' => $client,
          'contact_type' => [
            ContactTypeEnum::RESIDENTIAL_CONTACT->value,
            ContactTypeEnum::COMMERCIAL_CONTACT->value,
          ],
          'sources' => [
            ContactSourceEnum::TIK_TOK->value,
            ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
            ContactSourceEnum::META->value,
            ContactSourceEnum::DESTINO_TOLK->value,
            ContactSourceEnum::RESOURCE_MAGAZINE->value,
            ContactSourceEnum::BANNER_PUBLICITARIO->value,
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::GOOGLE_MY_BUSINESS->value,
            ContactSourceEnum::PICHY_BOYS->value,
          ],
          'clients' => $client->load([
              'clientAddress',
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateClientRequest $updateClientRequest, UpdateClient $updateUser, Client $client)
    {
        $updateUser->handle($updateClientRequest, $client);
        return redirect()->route('client.index')
          ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()
          ->back()
          ->with('success', 'Client deleted successfully.');
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
