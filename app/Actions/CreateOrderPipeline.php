<?php

namespace App\Actions;

use App\Enum\PlaningDateSupervisorEnum;
use App\Models\Client;
use App\Support\ReferralResolver;
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

  public function __construct(
    private readonly ReferralResolver $referralResolver
  ) {}
 
  public function handle(Request $request)
  {
    DB::transaction(function () use ($request) {
      $referral = $this->referralResolver->resolve($request->all());
      $client = Client::create([
        'name' => $request->client_name,
        'phone' => $request->phone,
        'source' => $request->source,
        'user_id' => auth()->user()->id,
        'is_contact' => false,
        'referral_id' => $referral?->id,
      ]);
    
      $status = $request->status;
      $order = Order::create([
        'client_id' => $client->id,
        'user_id' => auth()->user()->id,
        'name' => $request->client_name,
        'notes' => $request->notes,
        'status' => $status,
        'name_check' => $request->boolean('name_check'),
        'address_check' => $request->boolean('address_check'),
        'amount_check' => $request->boolean('amount_check'),
        'email_check' => $request->boolean('email_check'),
        
      ]);

      if ($request->filled('notes')) {
        $order->notes()->create([
          'content' => $request->notes,
          'type' => 'order_note',
          'user_id' => auth()->id(),
        ]);
      }

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
