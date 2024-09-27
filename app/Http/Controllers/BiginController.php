<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Carbon\Carbon;

class BiginController extends Controller
{
    use \App\Traits\Bigin; //Todo: remove this to a trait

    public function index()
    {
        return Inertia::render('Bigin/Index', [
            'bigin' => [
                'client_id' => config('bigin.client_id'),
                'redirect_uri' => route('bigin.callback'),
            ],
            'token' => Setting::where('name', 'BIGIN_TOKEN')->first()->value != '' ? true : false,
            'expire_in' => Setting::where('name', 'BIGIN_TOKEN_EXPIRES_IN')->first()->value
        ]);
    }

    public function callback(Request $request) 
    {
      if ($request->filled('code')) {
        $code = $request->input('code');
        $this->getAccessToken($code);
        return redirect()->route('bigin.index')
          ->with('success', 'Bigin token saved successfully');
      }

      return redirect()->route('bigin.index')
        ->with('error', 'Bigin token not saved');
    }

    /*public function storeContact(Request $request)
    {
      $contact = [
        'Last_Name' => 'Losada',
        'Email' => 'losada24@gmail.com',
        'Mobile' => '2397632058',
        'Description' => 'This user is generated associate program',
        'Source' => 'Employee Referral',
      ];

      $result = $this->createContact($contact);
      dd($result);
    } */
}
