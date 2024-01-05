<?php
namespace App\Actions;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enum\RoleEnum;

class UpdateCompany {

  public function handle(Request $request, Company $company) {

    DB::transaction(function() use ($request, $company) {

      if( !$company )
      {
          throw new \Exception('Not not updated');
      }

      $reaturedImagePath = $company->featured_image;
      if ($request->hasFile('featured_image')) {
        $fileName = time() . '_' . $request->file('featured_image')->getClientOriginalName();
        $tempOldImagePath = $reaturedImagePath;
        $reaturedImagePath = $request->file('featured_image')->storeAs('companies_images', $fileName, 'public');
        if ($reaturedImagePath && $tempOldImagePath) {
          Storage::disk('public')->delete($tempOldImagePath);
        }
      }
      
      $companyData = [
        'name' => $request->name,
        'phone_number' => $request->phone_number,
        'address' => $request->address,
        'city' => $request->city,
        'state' => $request->state,
        'zip' => $request->zip,
        'featured_image' => $reaturedImagePath,
        'user_id' => auth()->user()->id,
      ];

      if (auth()->user()->hasRole(RoleEnum::$ADMIN)) {
        $companyData['markup'] = $request->markup;
        $companyData['promotion'] = $request->promotion;
        $companyData['allow_credit_payment'] = $request->allow_credit_payment;
      }

      $company->update($companyData);
    });
  }
}
