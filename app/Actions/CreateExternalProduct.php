<?php
namespace App\Actions;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Models\ExternalProductConfiguration;

class CreateExternalProduct {

  public function handle(Request $request) {
    
    dd($request->all());
    DB::transaction(function() use ($request) {

      $externalProduct = ExternalProductConfiguration::create([
        'external_product' => $request->external_product,
        'width' => $request->width,
        'height' => $request->height,
        'price' => $request->price,
        'extras' => $request->extras,
        'notes' => $request->extras,
      ]);

      if( !$externalProduct )
      {
          throw new \Exception('Client not created');
      }

    });
  }
}
