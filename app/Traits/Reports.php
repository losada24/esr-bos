<?php

namespace App\Traits;

use App\Enum\OrderStatusEnum;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

trait Reports {
  
  public function getOrdersBySupervisor($id) {
     
      $orders = Order::with(['orderStatus', 'owners', 'installationTeams'])
          ->where('supervisor_id', $id)
          ->whereDate('installation_date', '<=', Carbon::today())
          ->where('status', '!=', OrderStatusEnum::PLANNED->value) 
          ->orderBy('created_at', 'desc')
          ->get();


          $total_amount = $orders->sum('project_amount');
          $total_commissions = $orders->sum('supervisor_commissions');
          //dd($total_amount,$total_commissions); 

      return $orders->map(function($order, $key) use ($total_amount,$total_commissions) {
        $final_installation_date_status = $order->orderStatus->where('status', OrderStatusEnum::COMPLETE->value)->first();
        $final_installation_date = null;
        $qty_days = 0;
        if ($final_installation_date_status) {
          $final_installation_date = Carbon::parse($final_installation_date_status->created_at)->format('m/d/Y');
          $qty_days = Carbon::parse($final_installation_date_status->created_at)->diffInDays(Carbon::parse($order->installation_date));
        } else {
          $qty_days = Carbon::parse($order->installation_date)->diffInDays(Carbon::now());
        }
         
          
        return [
          'id' => $order->id,
          'name' => $order->name,
          'city' => $order->city,
          'owners' => $order->owners->map(function($owner, $key) {
            return [
              'id' => $owner->id,
              'name' => $owner->name,
            ];
          }),
          'installation_team' => $order->installationTeams->map(function($team, $key) {
            return [
              'id' => $team->id,
              'company_name' => $team->company_name,
            ];
          }),
          'month' => Carbon::parse($order->installation_date)->format('F'),
          'installation_date' => Carbon::parse($order->installation_date)->format('m/d/Y'),
          'final_installation_date' => $final_installation_date,
          'execution_planing_date' => $order->execution_planing_date,
          'qty_days' => $qty_days,
          'project_amount' => $order->project_amount,
          'supervisor_payment_percentage' => $order->supervisor_payment_percentage,
          'supervisor_commissions' => $order->supervisor_commissions,
          'supervisor_payment_status' => $order->supervisor_payment_status,
          'supervisor_payment_date' => $order->supervisor_payment_date,
          'city_permits' => $order->city_permits,
          'total_amount'=> $total_amount,
          'total_commissions'=> $total_commissions,
        ];
      });
  }
}
