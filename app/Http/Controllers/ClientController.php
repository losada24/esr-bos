<?php

namespace App\Http\Controllers;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Actions\CreateClient;
use App\Actions\UpdateClient;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\UserResource;
use App\Enum\States;
use App\Models\Company;

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
          'clients' => Client::filter($request->only(['text']))
            ->orderBy('name')
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
        return Inertia::render('Client/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreClientRequest $storeClientRequest, CreateClient $createClient)
    {
        $createClient->handle($storeClientRequest);
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
        return Inertia::render('Client/Edit', [
          'client' => $client,
          'states' => array_values(States::$USA_STATES),
          'companies' => Company::orderBy('name')->orderBy('name')->get()
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
    public function isUnique($email, $phone)
    {
        $address = [];
        $clientsByEmail = Client::with(['clientAddress'])->where('email', $email)->get();
        $clientsByPhone = Client::with(['clientAddress'])->where('phone', $phone)->get();

        
        dd($clientsByEmail);

        foreach ($clientsByEmail->clientAddress as $clientAddress) {
          if (!in_array($clientAddress->address, $address)) {
            $address[] = $clientAddress->address;
          }
        }

        /*foreach ($clientsByPhone->clientAddress as $clientAddress) {
          if (!in_array($clientAddress->address, $address)) {
            $address[] = $clientAddress->address;
          }
        }

        return response()->json([
          'address' => $address
        ]);*/
    }
}
