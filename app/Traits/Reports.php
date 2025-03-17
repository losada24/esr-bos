<?php

namespace App\Traits;

use App\Enum\OrderStatusEnum;
use App\Models\Biweekly;
use App\Models\InstallationPayment;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
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

  public function getOrdersByInstaller($id, $status=null, $startDate=null, $endDate=null, $orderStatus=null) {

    $orders = Order::whereNotIn('status', [OrderStatusEnum::PLANNED->value, OrderStatusEnum::CONFIRMED->value])
    ->whereHas('installationTeams', function ($query) use ($id) {
        $query->whereHas('user', function ($subQuery) use ($id) {
            $subQuery->where('id', $id);
        });
    })
    ->where(function ($query) use ($status) {
      if ($status) {
          // ✅ Filtrar por el estado seleccionado
          $query->whereHas('paymentExtraFields', function ($subQuery) use ($status) {
              $subQuery->where('installer_payment_status', $status);
          });
      } else {
          // ✅ Mostrar todas las órdenes excepto FULLY PAID (incluyendo las que no tienen estado)
          $query->where(function ($subQuery) {
              $subQuery->whereDoesntHave('paymentExtraFields')
                       ->orWhereHas('paymentExtraFields', function ($innerQuery) {
                           $innerQuery->where('installer_payment_status', '!=', 'FULLY PAID');
                       });
          });
      }
  })
              ->when($orderStatus, function ($query) use ($orderStatus) {
                $query->where('status', $orderStatus);
            })

            ->when($startDate, function ($query) use ($startDate) {
              $query->whereHas('installationPayments', function ($subQuery) use ($startDate) {
                  $subQuery->whereDate('payment_date', '>=', $startDate);
              });
          })
          ->when($endDate, function ($query) use ($endDate) {
              $query->whereHas('installationPayments', function ($subQuery) use ($endDate) {
                  $subQuery->whereDate('payment_date', '<=', $endDate);
              });
          })
    ->with(['supervisor', 'orderProducts', 'travelCost', 'paymentExtraFields', 'installationPayments','installationTeams'])
    ->orderBy('installation_date', 'desc')
    ->get();
    return $orders->map(function($order, $key) {
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
      $amount = $order->getGrandTotalPrice();

      $installer= User::find($order->installationTeams->first()->user_id);
      $company = InstallationTeam::where('user_id', $order->installationTeams->first()->user_id)->first();
      
      //dd($company->company_name);
   
      $paymentExtraFields = $order->paymentExtraFields;

          if ($paymentExtraFields) {
              $transformedFields = [
                  'id' => $paymentExtraFields->id,
                  'responsible_extra_work' => $paymentExtraFields->responsible_extra_work,
                  //'documents_submitted' => $paymentExtraFields->documents_submitted,
                  //'collected_payment' => $paymentExtraFields->collected_payment,
                  'notes' => $paymentExtraFields->notes,
                  'installer_payment_status' => $paymentExtraFields->installer_payment_status,
                  //'extra_work' => $paymentExtraFields->extra_work,
                  //'extra_discount' => $paymentExtraFields->extra_discount,
                  
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
        'installation_payments' => $order->installationPayments->map(function($payment, $key) {
          return [
            'id' => $payment->id,
            'percentage_payment' => $payment->percentage_payment,
            'payment_date' => $payment->payment_date,
            'installer_payment' => $payment->installer_payment,
            'extra_work' => $payment->extra_work,
            'extra_discount' => $payment->extra_discount,
            'other_cost_installer' => $payment->other_cost_installer,
            'notes' => $payment->notes,
            'responsible_extra_work' => $payment->responsible_extra_work,
            'order_id' => $payment->order_id,
            'installation_team_id' => $payment->installation_team_id,


          ];
        }),
        'company_name' => $company->company_name,
        'amount' => $amount,
        //'month' => Carbon::parse($order->installation_date)->format('F'),
        'installation_date' => Carbon::parse($order->installation_date)->format('m/d/Y'),
        'final_installation_date' => $final_installation_date,
        'inspection_installation_date' => $inspection_installation_date,
        'pre_inspection' => $order->pre_inspection,
        'inspection' => $order->inspection,
        'walk_trough' => $order->walk_trough,
        'partial_payment_installation' => $order->partial_payment_installation,
        'final_payment_installation' => $order->final_payment_installation,
        'status' => $order->status,
        'installer' => $installer->name,
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

   public function getOrdersByInstallerBiweekly($id) {
            
    $payments = InstallationPayment::with(['order.supervisor', 'order.orderStatus', 'order.paymentExtraFields', 'biweekly'])
    ->where('biweekly_id', $id)
    ->get();
                      
      //dd($payments);

     
  return $payments->map(function($payment, $key) {
                  //dd($order);
      $biweekly = Biweekly::find($payment->biweekly_id);
      $installer= User::find($payment->installation_team_id);   
      $company = InstallationTeam::where('user_id', $payment->installation_team_id)->first();    
      $final_installation_date_status = $payment->order->orderStatus->where('status', OrderStatusEnum::COMPLETE->value)->first();
      $inspection_date_status = $payment->order->orderStatus->where('status', OrderStatusEnum::INSPECTION->value)->first();
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
        $amount = $payment->order->getGrandTotalPrice();

        $paymentExtraFields = $payment->order->paymentExtraFields;

        if ($paymentExtraFields) {
              $transformedFields = [
                  'id' => $paymentExtraFields->id,
                  'responsible_extra_work' => $paymentExtraFields->responsible_extra_work,
                  'notes' => $paymentExtraFields->notes,
                  'installer_payment_status' => $paymentExtraFields->installer_payment_status,
                ];
        } else {
              $transformedFields = []; // Retorna un array vacío si no existe
        }
         return [
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'name' => $payment->order->name,
                    'initial_payment_percentage' => $payment->order->initial_payment_percentage,
                    'owners' => $payment->order->owners->map(function($owner, $key) {
                      return [
                        'id' => $owner->id,
                        'name' => $owner->name,
                      ];
                    }), 
                    'biweekly' => Carbon::parse($biweekly->start_biweekly_period)->toFormattedDateString().' to '. Carbon::parse($biweekly->end_biweekly_period)->toFormattedDateString(),
                    'supervisor'=> $payment->order->supervisor->name,
                    'installation_team' => $installer->name,
                    'company_name' => $company->company_name,
                    'installer_payment' => $payment->installer_payment,
                    'percentage_payment' => $payment->percentage_payment,
                    'payment_date' => $payment->payment_date,
                    'extra_work' => $payment->extra_work,
                    'extra_discount' => $payment->extra_discount,
                    'other_cost_installer' => $payment->other_cost_installer,
                    'notes' => $payment->notes,
                    'responsible_extra_work' => $payment->responsible_extra_work,
                    'amount' => $amount,
                    //'month' => Carbon::parse($order->installation_date)->format('F'),
                    'installation_date' => Carbon::parse($payment->order->installation_date)->format('m/d/Y'),
                    'final_installation_date' => $final_installation_date,
                    'inspection_installation_date' => $inspection_installation_date,
                    'pre_inspection' => $payment->order->pre_inspection,
                    'inspection' => $payment->order->inspection,
                    'walk_trough' => $payment->order->walk_trough,
                    'partial_payment_installation' => $payment->order->partial_payment_installation,
                    'final_payment_installation' => $payment->order->final_payment_installation,
                    //'project_amount' => $order->project_amount,
                    //'supervisor_payment_percentage' => $order->supervisor_payment_percentage,
                    //'supervisor_commissions' => $order->supervisor_commissions,
                    //'supervisor_payment_status' => $order->supervisor_payment_status,
                  // 'supervisor_payment_date' => $order->supervisor_payment_date,
                    'city_permits' => $payment->order->city_permits,
                    'payment_extra_fields' => $transformedFields,
                    //'total_amount'=> $total_amount,
                    //'total_commissions'=> $total_commissions,
                  ];
                
                });
            }
}
