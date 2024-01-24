<?php

namespace App\Http\Controllers;

use App\Enum\ProductSystemEnum;
use App\Enum\RoleEnum;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
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

    public function cuttingList(Order $order)
    {
      $order->load(['products', 'client']);
      $orderCuttingList = $this->orderedCuttingList($order);

      $orderData = [
        'id' => $order->id,
        'name' => $order->name,
        'client' => $order->client,
        'created_at' => $order->created_at,
        'project_name' => $order->project_name,
        'orderCuttingList' => $orderCuttingList,
      ];
      return Inertia::render('Pdf/CuttingList', [
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

    public function poBalance(Order $order)
    {
      $order->load(['products' => function($query) {
        $query->where('system', ProductSystemEnum::$SINGLE_HUNG);
      }, 'client']);
      
      $poBalance = $this->getBalancePO($order);
      
      $orderData = [
        'id' => $order->id,
        'name' => $order->name,
        'client' => $order->client,
        'created_at' => $order->created_at,
        'project_name' => $order->project_name,
        'balances' => $poBalance,
      ];
      return Inertia::render('Pdf/PoBalance', [
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

    public function report(Order $order)
    {
      $order->load(['products', 'client', 'user.company']);
      $company = Company::where('id', $order->user->company_id)->first();
      CompanyResource::withoutWrapping();
      return Inertia::render('Pdf/Report', [
        'order' => $order,
        'company' => new CompanyResource($company),
      ]);
    }

    public function estimateWithPrices(Order $order)
    {
      $order->load(['products', 'client', 'user.company']);
      $company = new \stdClass();
      $company->id = auth()->user()->company_id;
      $company->email = auth()->user()->email;
      $company->phone_number = '';
      $company->address = '';
      $company->featured_image = $order->user->company->featured_image;
      if (auth()->user()->hasRole(RoleEnum::$DEALER)) {
        $company = Company::where('id', $order->user->company_id)->first();
      }
      
      $company = new CompanyResource($company);
      CompanyResource::withoutWrapping();
      return Inertia::render('Pdf/EstimateWithPrices', [
        'order' => $order,
        'company' => $company,
      ]);
    }

    public function estimateWithoutPrices(Order $order)
    {
      $order->load(['products', 'client', 'user.company']);
      $company = Company::where('id', $order->user->company_id)->first();
      CompanyResource::withoutWrapping();
      return Inertia::render('Pdf/EstimateWithoutPrices', [
        'order' => $order,
        'company' => new CompanyResource($company) ,
      ]);
    }

    public function estimateWithTotalPrices(Order $order)
    {
      $order->load(['products', 'client', 'user.company']);
      $company = Company::where('id', $order->user->company_id)->first();
      CompanyResource::withoutWrapping();
      return Inertia::render('Pdf/EstimateWithTotalPrices', [
        'order' => $order,
        'company' => new CompanyResource($company),
      ]);
    }

    public function delivery(Order $order)
    {
      $order->load(['products', 'client', 'user.company']);
      return Inertia::render('Pdf/Delivery', [
        'order' => $order,
      ]);
    }
}
