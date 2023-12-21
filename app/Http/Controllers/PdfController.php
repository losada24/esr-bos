<?php

namespace App\Http\Controllers;

use App\Enum\ProductSystemEnum;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Traits\Product;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;

class PdfController extends Controller
{
    use Product;

    public function workOrder(Order $order)
    {
      $order->load(['products', 'client']);
      
      // $materialConsumption = $this->getMaterialConsumption($order);
      $cuttingList = $this->getCuttingList($order);

      $orderData = [
        'id' => $order->id,
        'name' => $order->name,
        'client' => $order->client,
        'created_at' => $order->created_at,
        'project_name' => $order->project_name,
        'products' => $cuttingList,
        // 'materialConsumption' => $materialConsumption
      ];
      return Inertia::render('Pdf/WorkOrder', [
        'order' => $orderData
      ]);
    }

    public function materialConsumption(Order $order)
    {
      $order->load(['products', 'client']);
      
      $materialConsumption = $this->getMaterialConsumption($order);

      $orderData = [
        'id' => $order->id,
        'name' => $order->name,
        'client' => $order->client,
        'created_at' => $order->created_at,
        'project_name' => $order->project_name,
        'materialConsumption' => $materialConsumption
      ];
      return Inertia::render('Pdf/MaterialConsumption', [
        'order' => $orderData
      ]);
    }

    public function poScreen(Order $order)
    {
      $order->load(['products' => function($query) {
        $query->where('system', '<>', ProductSystemEnum::$FIXED_WINDOWS)
          ->where('extras->screen', true);
      }, 'client']);
      
      $poScreen = $this->getPOScreen($order);

      $orderData = [
        'id' => $order->id,
        'name' => $order->name,
        'client' => $order->client,
        'created_at' => $order->created_at,
        'project_name' => $order->project_name,
        'products' => $poScreen,
      ];
      return Inertia::render('Pdf/PoScreen', [
        'order' => $orderData
      ]);
    }

    public function poGlass(Order $order)
    {
      $order->load(['products', 'client']);
      
      $poGlass = $this->getPOGlass($order);

      $orderData = [
        'id' => $order->id,
        'name' => $order->name,
        'client' => $order->client,
        'created_at' => $order->created_at,
        'project_name' => $order->project_name,
        'products' => $poGlass,
      ];
      return Inertia::render('Pdf/POGlass', [
        'order' => $orderData
      ]);
    }
}
