<?php

namespace App\Http\Controllers;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateClient;
use App\Actions\UpdateClient;
use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\ClientAddress;
use App\Models\CompanyContact;
use Barryvdh\DomPDF\Facade\Pdf;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('Client/Index', [
          'clients' => Client::with(['clientAddress', 'user'])->filter($request->only(['text']))
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
        return Inertia::render('Client/Create', [ 
          'contact_type' => [
          ContactTypeEnum::RESIDENTIAL_CONTACT->value,
          ContactTypeEnum::COMMERCIAL_CONTACT->value,
        ],
        'companies' => CompanyContact::all(),
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
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
       ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreClientRequest $storeClientRequest, CreateClient $createClient)
    {
        $client = $createClient->handle($storeClientRequest);
            //dd($client);
        if ($storeClientRequest->boolean('from_modal')) {
            $client->load([
                'referral',
                'referral.referrerClient:id,name,phone,email',
                'referral.referrerUser:id,name,phone,email,status',
            ]);

            return response()->json(['client' => $client]);
        }
        return redirect()->route('client.index')
          ->with('success', 'Client created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {    
        $client->load('tags:id,name,color,taggable_id,taggable_type');
        return Inertia::render('Client/Edit', [
          //'clients' => $client,
          'contact_type' => [
            ContactTypeEnum::RESIDENTIAL_CONTACT->value,
            ContactTypeEnum::COMMERCIAL_CONTACT->value,
          ],
          'companies' => CompanyContact::all(),
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
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
          ],
          'clients' => $client->load([
              'clientAddress',
              'referral',
              'referral.referrerClient:id,name,phone,email',
              'referral.referrerUser:id,name,phone,email,status',
            ]),

            'tags' => $client->tags->map(fn($t) => [
                'name'  => $t->name,
                'color' => $t->color,
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
            if ($client->orders()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'The client cannot be deleted because they are associated with one or more orders.');
        }

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

    public function phoneExists(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'ignore_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Client::query()->where('phone', $data['phone']);
        if (!empty($data['ignore_id'])) {
            $query->where('id', '!=', (int) $data['ignore_id']);
        }

        return response()->json([
            'exists' => $query->exists(),
        ]);
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $term = trim($data['q']);
        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $digits = preg_replace('/\D+/', '', $term) ?? '';
        $like = '%' . $term . '%';

        $clients = Client::query()
            ->select('id', 'name', 'phone', 'phone_ext', 'email', 'secondary_email', 'vip_clients', 'vip_notes', 'company_contact_id')
            ->with(['companyContact:id,name,email', 'companyContacts:id,name,email'])
            ->where(function ($query) use ($like, $digits) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);

                if ($digits !== '') {
                    $query->orWhere('phone', 'like', '%' . $digits . '%');
                } else {
                    $query->orWhere('phone', 'like', $like);
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json(['data' => $clients]);
    }

    public function document($id)
    {
        $clientAddress = ClientAddress::with(['client', 'client.user'])->find($id);
        $pdf = Pdf::loadView('pdf.sale-form', ['clientAddress' => $clientAddress]);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('sale-form.pdf');
    }
}
