<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class CreateEstimateOrder {

  public function handle(Request $request, Order $estimate) {
    
    DB::transaction(function() use ($request, $estimate) {

      if( !$estimate )
      {
          throw new \Exception('Not not updated');
      }

      $payment = [
        'method' => $request->method,
        'street_address' => $request->street_address,
        'city' => $request->city,
        'state' => $request->state,
        'zip_code' => $request->zip_code,
        'country' => $request->country,
        'notes' => $request->notes,
        'amount' => $request->amount,
        'user_id' => auth()->user()->id,
      ];

      $estimate->payments()->create($payment);

      $estimateData = [
        'status' => OrderStatusEnum::$ACCOUNTING,
      ];

      $estimate->update($estimateData);
      $estimate->orderStatus()->create([
        'status' => OrderStatusEnum::$ACCOUNTING,
        'user_id' => auth()->user()->id,
        'notes' => "Payment was submitted by " . auth()->user()->name . " using " . $request->method
      ]);

      // SEND EMAILS
      $emailsForStatus = Email::where('status', OrderStatusEnum::$ACCOUNTING)->first();
      $recipientsArray = [];
      if ($emailsForStatus) {
        $recipientsArray = explode(',', $emailsForStatus->recipients);
      }

      $dealersUsers = User::whereHas('roles', function($q) {
        $q->where('name', RoleEnum::$DEALER);
      })->where('company_id', $estimate->company_id)->get();
      $dealersEmails = $dealersUsers->pluck('email')->toArray();

      foreach ([...$recipientsArray, ...$dealersEmails] as $recipient) {
        Mail::to($recipient)->send(new \App\Mail\OrderCreated($estimate));
      }
    });
  }
}
