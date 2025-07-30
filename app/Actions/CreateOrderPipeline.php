<?php

namespace App\Actions;

use App\Enum\PlaningDateSupervisorEnum;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\SupervisorComissionOrder;
use App\Traits\ComissionSupervisor;
use App\Traits\OrderEmails;
use App\Traits\OrderStatus;
use Illuminate\Support\Str;

class CreateOrderPipeline
{

  use OrderEmails, OrderStatus, ComissionSupervisor;
 
  public function handle(Request $request)
  {
    DB::transaction(function () use ($request) {
      $client = Client::create([
        'name' => $request->client_name,
        'phone' => $request->phone,
        'source' => $request->source,
        'user_id' => auth()->user()->id,
        'is_contact' => false,
      ]);
    
      $status = $request->status;
      $order = Order::create([
        'client_id' => $client->id,
        'user_id' => auth()->user()->id,
        'name' => $request->client_name,
        'notes' => $request->notes,
        'status' => $status,
        
      ]);

      $order->orderStatus()->create([
        'status' => $status,
        'user_id' => auth()->user()->id,
        'notes' => "$status created by " . auth()->user()->name,
      ]);
      if (!$order) {
        throw new \Exception('Order not created');
      }
    });
  }
}
