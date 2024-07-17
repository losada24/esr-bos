<?php
namespace App\Actions;

use App\Enum\GlassLowEEnum;
use App\Enum\GlassTypeEnum;
use App\Enum\ProductSystemEnum;
use App\Enum\RoleEnum;
use App\Models\Order;
use App\Products\FixedWindowsProduct;
use App\Products\Glass;
use App\Products\HorizontalRollerProduct;
use App\Products\SingleHuntProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateOrder {

  public function handle(Request $request, Order $order) {

    DB::transaction(function() use ($request, $order) {

      if( !$order )
      {
          throw new \Exception('Not not updated');
      }

      
    });
  }
}
