<?php
namespace App\Actions;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreateCompany {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $reaturedImagePath = null;
      if ($request->hasFile('featured_image')) {
        $fileName = time() . '_' . $request->file('featured_image')->getClientOriginalName();
        $reaturedImagePath = $request->file('featured_image')->storeAs('companies_images', $fileName, 'public');
      }

      $company = Company::create([
        'name' => $request->name,
        'phone_number' => $request->phone_number,
        'address' => $request->address,
        'city' => $request->city,
        'state' => $request->state,
        'zip' => $request->zip,
        'featured_image' => $reaturedImagePath,
        'user_id' => auth()->user()->id
      ]);

      if( !$company )
      {
          throw new \Exception('Company not created');
      }

    });
  }
}
