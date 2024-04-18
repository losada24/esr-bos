<?php
namespace App\Actions;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ProduceOrder {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $order = Order::find($request->id);
      $orderStatus = [
        'status' => $request->status,
        'user_id' => auth()->user()->id,
        'notes' => $request->notes,
      ];
      
      if( !$order )
      {
          throw new \Exception('Not not updated');
      }

      $orderData = [
        'status' => $request->status
      ];

      $order->update($orderData);
      $order->orderStatus()->create($orderStatus);

      // SEND EMAILS
      $dealersUsers = User::whereHas('roles', function($q) {
        $q->where('name', RoleEnum::$DEALER);
      })->where('company_id', $order->company_id)->get();

      $dealersEmails = $dealersUsers->pluck('email')->toArray();

      $emailsForStatus = Email::where('status', $request->status)->first();
      $recipientsArray = [];
      if ($emailsForStatus) {
        $recipientsArray = explode(',', $emailsForStatus->recipients);
      }

      if ($request->status == OrderStatusEnum::$ESTIMATE && auth()->user()->hasRole(RoleEnum::$SUB_DEALER)) {
          $adminUsers = User::whereHas('roles', function($q) {
            $q->where('name', RoleEnum::$ADMIN);
          })->get();
        
          $accountManager = User::whereHas('roles', function($q) {
            $q->where('name', RoleEnum::$ACCOUNT_MANAGER);
          })->get();

          $users = [
            ...$adminUsers,
            ...$accountManager,
            ...$dealersUsers
          ];

          foreach ($users as $recipient) {
            Mail::to($recipient->email, $recipient->name)->send(new \App\Mail\EstimateCreated($order, [RoleEnum::$DEALER]));
          }
      } elseif ($request->status == OrderStatusEnum::$PRODUCTION) {
        foreach ([...$recipientsArray, ...$dealersEmails] as $recipient) {
          Mail::to($recipient)->send(new \App\Mail\ProductionOrder($order));
        }
      } elseif ($request->status == OrderStatusEnum::$SCHEDULED_PRODUCTION) {
        foreach ($recipientsArray as $recipient) {
          Mail::to($recipient)->send(new \App\Mail\ProductionScheduled($order, $request->notes));
        }
      } elseif ($request->status == OrderStatusEnum::$PRODUCTION_IN_PROGRESS) {
        foreach ($recipientsArray as $recipient) {
          Mail::to($recipient)->send(new \App\Mail\ProductionInProgress($order));
        }
      } elseif ($request->status == OrderStatusEnum::$PRODUCTION_COMPLETED || $request->status == OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED) { // OK
        foreach ([...$recipientsArray, ...$dealersEmails] as $recipient) {
          Mail::to($recipient)->send(new \App\Mail\ProductionStatusChange($order, $request->status, $request->notes));
        }
      } elseif (
        $request->status == OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY || 
        $request->status == OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP ||
        $request->status == OrderStatusEnum::$READY_FOR_DELIVERY ||
        $request->status == OrderStatusEnum::$READY_FOR_PICKUP) {
          foreach ([...$recipientsArray, ...$dealersEmails] as $recipient) {
            Mail::to($recipient)->send(new \App\Mail\DeliveryOrPickupChange($order, $request->status));
          }
      } elseif (
        $request->status == OrderStatusEnum::$PARTIAL_DELIVERED || 
        $request->status == OrderStatusEnum::$PARTIAL_PICKED_UP ||
        $request->status == OrderStatusEnum::$DELIVERED ||
        $request->status == OrderStatusEnum::$PICKED_UP) {
          foreach ([...$recipientsArray, ...$dealersEmails] as $recipient) {
            Mail::to($recipient)->send(new \App\Mail\DeliveryOrPickupChange($order, $request->status));
          }
      } elseif (
        $request->status == OrderStatusEnum::$ORDER_COMPLETED ) {
          foreach ([...$recipientsArray, ...$dealersEmails] as $recipient) {
            Mail::to($recipient)->send(new \App\Mail\OrderCompletion($order));
          }
      }
    });
  }
}
