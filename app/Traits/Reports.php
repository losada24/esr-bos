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

  public function getOrdersByInstaller($id) {

  $orders = Order::where('status', '!=', OrderStatusEnum::PLANNED->value)  // Filtrar las órdenes cuyo estado no es 'planeada'
    ->whereHas('installationTeams', function ($query) use ($id) {
        // Filtra las órdenes que tienen equipos de instalación asociados con el instalador específico
        $query->whereHas('user', function ($subQuery) use ($id) {
            $subQuery->where('id', $id);  // Filtra por el instalador específico
        });
    })
    ->with(['supervisor','orderProducts','travelCost','paymentExtraFields','installationPayments'])  // Cargar la relación con el supervisor directamente desde la orden
    ->get();

    //dd($orders->toArray());
        //$total_amount = $orders->sum('project_amount');
        //$total_commissions = $orders->sum('supervisor_commissions');
    return $orders->map(function($order, $key) {

      //dd($order->getGrandTotalPrice());
     
      $final_installation_date_status = $order->orderStatus->where('status', OrderStatusEnum::COMPLETE->value)->first();
      $inspection_date_status = $order->orderStatus->where('status', OrderStatusEnum::INSPECTION->value)->first();
      if ($final_installation_date_status) {
        $final_installation_date = Carbon::parse($final_installation_date_status->created_at)->format('m/d/Y');
      } else {
        $final_installation_date = null;
      }
      if ($inspection_date_status) {
        $inspection_installation_date = Carbon::parse($inspection_date_status->created_at)->format('m/d/Y');
      } else {
        $inspection_installation_date = null;
      }
      /*$travel= $order->travelCost->price;
      $amount = $order->orderProducts->sum(function($product) {
        return $product->total_price + $product->extra_work_price;
      });

      if ($order->additional_travel_costs) {
        $amount = $amount + $order->additional_travel_costs;
      } */
      $amount = $order->getGrandTotalPrice();
      $payment= $order->installationPayments->all();

    //dd($payment);

      $paymentExtraFields = $order->paymentExtraFields;

          if ($paymentExtraFields) {
              $transformedFields = [
                  'id' => $paymentExtraFields->id,
                  'responsible_extra_work' => $paymentExtraFields->responsible_extra_work,
                  'documents_submitted' => $paymentExtraFields->documents_submitted,
                  'collected_payment' => $paymentExtraFields->collected_payment,
                  'notes' => $paymentExtraFields->notes,
                  'installer_payment_status' => $paymentExtraFields->installer_payment_status,
                  'extra_work' => $paymentExtraFields->extra_work,
                  'extra_discount' => $paymentExtraFields->extra_discount,
                  
              ];
          } else {
              $transformedFields = []; // Retorna un array vacío si no existe
          }

        
      return [
        'id' => $order->id,
        'name' => $order->name,
        'initial_payment_percentage' => $order->initial_payment_percentage,
        'owners' => $order->owners->map(function($owner, $key) {
          return [
            'id' => $owner->id,
            'name' => $owner->name,
          ];
        }),
        'supervisor'=> $order->supervisor->name,
        'installation_team' => $order->installationTeams->map(function($team, $key) {
          return [
            'id' => $team->id,
            'company_name' => $team->company_name,
          ];
        }),
        'amount' => $amount,
        //'month' => Carbon::parse($order->installation_date)->format('F'),
        'installation_date' => Carbon::parse($order->installation_date)->format('m/d/Y'),
        'final_installation_date' => $final_installation_date,
        'inspection_installation_date' => $inspection_installation_date,
        //'execution_planing_date' => $order->execution_planing_date,
        //'qty_days' => $qty_days,
        //'project_amount' => $order->project_amount,
        //'supervisor_payment_percentage' => $order->supervisor_payment_percentage,
        //'supervisor_commissions' => $order->supervisor_commissions,
        //'supervisor_payment_status' => $order->supervisor_payment_status,
       // 'supervisor_payment_date' => $order->supervisor_payment_date,
        'city_permits' => $order->city_permits,
        'payment_extra_fields' => $transformedFields,
        //'total_amount'=> $total_amount,
        //'total_commissions'=> $total_commissions,
      ];
     
    });
}
}
