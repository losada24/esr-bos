<?php
namespace App\Actions;

use App\Enum\GlassTypeEnum;
use App\Enum\ProductSystemEnum;
use App\Enum\RoleEnum;
use App\Models\ExternalProductConfiguration;
use App\Models\Order;
use App\Products\FixedWindowsProduct;
use App\Products\Glass;
use App\Products\HorizontalRollerProduct;
use App\Products\SingleHuntProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateExternalProduct {

  public function handle(Request $request, ExternalProductConfiguration $externalProductConfiguration) {

    DB::transaction(function() use ($request, $externalProductConfiguration) {

      if( !$externalProductConfiguration )
      {
          throw new \Exception('Not not updated');
      }

      $externalProductData = [
        'external_product' => $request->external_product,
        'width' => $request->width,
        'height' => $request->height,
        'price' => $request->price,
        'extras' => json_decode($request->extras),
        'notes' => $request->notes,
      ];

      $externalProductConfiguration->update($externalProductData);

    });
  }
}
