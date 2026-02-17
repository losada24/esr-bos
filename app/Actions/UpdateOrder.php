<?php

namespace App\Actions;

use App\Enum\OrderStatusEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\MethodOfPayment;
use App\Events\OrderStatusChanged;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentExtraField;
use App\Models\PaymentSchedule;
use App\Support\OrderFinancialEventLogger;
use App\Support\PaymentScheduleCalculator;
use App\Support\PaymentScheduleTemplates;
use App\Traits\ComissionSupervisor;
use App\Traits\OrderEmails;
use App\Traits\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Traits\Twilio;
use Twilio\TwiML\Voice\Pay;

class UpdateOrder
{

  use OrderEmails, OrderStatus, Twilio, ComissionSupervisor;

  public function handle(Request $request, Order $order)
  {
    //dd($request->all());
    $order->loadMissing(['installationTeams']);
    $installer = $order->installationTeams()->pluck('installation_teams.id')->toArray();
    $currentInstallers = array_map('intval', $installer);
    $requestInstallers = array_map('intval', $request->installation_teams ?? []);

      sort($currentInstallers);
      sort($requestInstallers);

      $installationTeamsChanged = $currentInstallers !== $requestInstallers;
      $supervisorChanged = ((int) $order->supervisor_id !== (int) $request->supervisor_id);
    //dd($supervisorChanged);
    //dd($order->supervisor_id, $request->supervisor_id);
    DB::beginTransaction();
    try {
      $client = Client::find($request->client_id);
      if ($client) {
        $client->update([
          'name' => $request->client_name,
          'phone' => $request->phone,
          'email' => $request->email,
          'vip_clients' =>$request->vip_clients,
          'vip_notes' => $request->vip_notes,
          'contact_type' => $request->contact_type,
          'user_id' => auth()->user()->id,
        ]);
      }
      $order = Order::with('comissions')->findOrFail($order->id);
      $oldAmount = $order->project_amount;
      $newAmount = $request->project_amount;
      $hasCommissions = $order->comissions()->exists();

      if ($order->hasReachedContractSigned() && abs((float) $newAmount - (float) $oldAmount) > 0.01) {
        throw ValidationException::withMessages([
          'project_amount' => 'Project amount cannot be edited after CONTRACT SIGNED BY CLIENT. Use Change Order instead.',
        ]);
      }
  
     
       //dd( $oldAmount, $newAmount, $order);

      if ($order->service == ServiceEnum::INSTALLATION->value || $order->service == ServiceEnum::INSTALLATION_ONLY->value || $order->service == ServiceEnum::SERVICE->value) {
        $execution_planing_date = $order->execution_planing_date;
        if ($newAmount != $oldAmount && $hasCommissions) {
          // Eliminar comisiones previas
          $order->comissions()->delete();
      
          if ($newAmount > 0) {
              $commissions = $this->ComissionSupervisor($newAmount);
              $totalCommission = array_sum(array_column($commissions, 'amount'));
              foreach ($commissions as $data) {
                  $order->comissions()->create($data);
              }
          } else {
              // Si el nuevo monto es 0, no hay comisiones
              $totalCommission = 0;
          }
      
      } elseif (!$hasCommissions && $newAmount > 0) {
          // Crear comisiones si no hay y el monto es mayor a cero
          $commissions = $this->ComissionSupervisor($newAmount);
          $totalCommission = array_sum(array_column($commissions, 'amount'));
          foreach ($commissions as $data) {
              $order->comissions()->create($data);
          }
      
      } elseif ($newAmount == 0 && $hasCommissions) {
          // Si el nuevo monto es 0 y hay comisiones, eliminarlas
          $order->comissions()->delete();
          $totalCommission = 0;
      } else {
          // En todos los demás casos
          $totalCommission = $order->project_amount > 0 ? $order->comissions()->sum('amount') : 0;
      }
        
      if ($request->status == OrderStatusEnum::COMPLETE->value) {
        $supervisor_payment_status = SupervisorPaymentStatusEnum::PENDING->value;
      } else {
        $supervisor_payment_status = $order->supervisor_payment_status;
      }
      } else {
        $execution_planing_date = 0;
        $supervisor_payment_percentage = 0.00;
        $totalCommission = 0.00;
        $supervisor_payment_status = null;
      }

      if ($request->city_permits) {
        $initial_payment_percentage = 80.00;
      } else {
        $initial_payment_percentage = 100.00;
      }

      $status = $request->status;
      $type_of_work_id = $request->type_of_work_id;
      $type_of_housing_id = $request->type_of_housing_id;
      $travel_cost_id = $request->travel_cost_id;
      $duration_of_work_id = $request->duration_of_work_id;

      if ($type_of_work_id === 0 || $type_of_work_id === '0' || $type_of_work_id === '') {
        $type_of_work_id = null;
      }
      if ($type_of_housing_id === 0 || $type_of_housing_id === '0' || $type_of_housing_id === '') {
        $type_of_housing_id = null;
      }
      if ($travel_cost_id === 0 || $travel_cost_id === '0' || $travel_cost_id === '') {
        $travel_cost_id = null;
      }
      if ($duration_of_work_id === 0 || $duration_of_work_id === '0' || $duration_of_work_id === '') {
        $duration_of_work_id = null;
      }
      
      //dd($request->pending_collect);
      $sendEmail = $status != $order->status;
      //dd($sendEmail,$status,$order->status);
      $orderData = [
        'client_id' => $client->id,
        'user_id' => auth()->user()->id,
        'name' => $request->name,
        'job_address' => $request->job_address,
        // 'job_city' => $request->job_city ?? $request->city,
        'order_number' => $request->order_number,
        'invoice_number' => $request->invoice_number,
        'order_type' => $request->order_type,
        'type_of_work_id' => $type_of_work_id,
        'type_of_housing_id' => $type_of_housing_id,
        'supervisor_id' => $request->supervisor_id,
        'travel_cost_id' => $travel_cost_id,
        'duration_of_work_id' => $duration_of_work_id,
        'duration_of_work_id' => $duration_of_work_id,
        'method_of_payment' => $request->method_of_payment,
        'type_of_financing' => $request->type_of_financing,
        'service' => $request->service,
        'contract_signing_date' => $request->contract_signing_date,
        'payment_factory_date' => $request->payment_factory_date,
        'entry_date' => $request->entry_date,
        'eta_date' => $request->eta_date,
        'installation_date' => $request->installation_date,
        'installation_end_date' => $request->installation_end_date,
        'additional_travel_costs' => $request->additional_travel_costs,
        'city_permits' => $request->city_permits,
        'association_permits' => $request->association_permits,
        'equipment_rental' => $request->equipment_rental,
        'notes' => $request->notes,
        'delivery_date' => $request->delivery_date,
        'status' => $status,
        //'frame_color' => $request->frame_color,
        'cost_delivery' => $request->cost_delivery,
        'cost_city_fee' => $request->cost_city_fee,
        'project_amount' => $request->project_amount,
        'down_payment' => $request->down_payment,
        'city' => $request->city ,
        'job_state' => $request->job_state,
        'job_zip' => $request->job_zip,
        'initial_payment_percentage' => $initial_payment_percentage,
        'payment_definition' => $request->payment_definition,
        'execution_planing_date' => $execution_planing_date,
        //'supervisor_payment_percentage' => $supervisor_payment_percentage,
        'supervisor_commissions' => $totalCommission,
        'supervisor_payment_status' => $supervisor_payment_status,
        'hide_on_weekends' => $request->hide_on_weekends,
        'do_not_send_email' => $request->do_not_send_email,
        'is_new_travel_cost' => $request->is_new_travel_cost,
        'new_travel_cost' => $request->new_travel_cost,
        'material_received_date' => $request->material_received_date,
        'complete_date' => $request->complete_date,
        'pending_collect'=> $request->pending_collect,

        
      ];
      //dd( $orderData);
      //dd($request->frame_color);
      $order->update($orderData);

      $totalAmount = (float) ($request->project_amount ?? 0);
      $requiresSchedule = $request->method_of_payment === MethodOfPayment::CASH->value;
      $existingSchedule = $order->paymentSchedule()->with('installments')->first();
      $hasScheduleTypeInput = $request->exists('payment_schedule_type');
      $hasCustomScheduleInput = $request->exists('custom_schedule');
      $shouldProcessSchedule = !$requiresSchedule || $hasScheduleTypeInput || $hasCustomScheduleInput;
      $scheduleType = $requiresSchedule
        ? ($hasScheduleTypeInput
          ? (string) ($request->input('payment_schedule_type') ?? '')
          : (string) ($existingSchedule?->schedule_type ?? ''))
        : '';
      $customSchedule = $hasCustomScheduleInput ? $request->input('custom_schedule', []) : [];
      $hasRecordedPayments = $existingSchedule
        ? $existingSchedule->installments()->whereHas('movements')->exists()
        : false;

      if ($requiresSchedule && !$hasCustomScheduleInput && $scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value && $existingSchedule) {
        $customSchedule = $existingSchedule->installments
          ->sortBy('position')
          ->values()
          ->map(fn ($item) => [
            'label' => $item->label,
            'amount' => (float) $item->amount,
          ])->all();
      }

      if ($shouldProcessSchedule && $hasRecordedPayments) {
        if (!$requiresSchedule || !$existingSchedule || $scheduleType === '' || $scheduleType !== (string) $existingSchedule->schedule_type) {
          throw ValidationException::withMessages([
            'payment_schedule_type' => 'Payment schedule cannot be changed after payments are recorded.',
          ]);
        }

        if ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
          $incomingItems = collect($customSchedule)
            ->map(function ($item) {
              return [
                'label' => trim((string) ($item['label'] ?? '')),
                'amount' => round((float) ($item['amount'] ?? 0), 2),
              ];
            })
            ->filter(fn ($item) => $item['label'] !== '')
            ->values()
            ->all();

          $existingItems = $existingSchedule->installments
            ->sortBy('position')
            ->values()
            ->map(fn ($item) => [
              'label' => trim((string) $item->label),
              'amount' => round((float) $item->amount, 2),
            ])
            ->all();

          if ($incomingItems !== $existingItems) {
            throw ValidationException::withMessages([
              'payment_schedule_type' => 'Payment schedule cannot be changed after payments are recorded.',
            ]);
          }
        }
      } elseif ($shouldProcessSchedule) {
        if (!$requiresSchedule || $scheduleType === '') {
          if ($existingSchedule) {
            $previousScheduleType = $existingSchedule->schedule_type;
            $previousTotalAmount = (float) $existingSchedule->total_amount;
            $existingSchedule->installments()->delete();
            $existingSchedule->delete();

            OrderFinancialEventLogger::log(
              $order,
              'PAYMENT_SCHEDULE_REMOVED',
              'Payment schedule removed',
              [
                'before_schedule_type' => $previousScheduleType,
                'before_total_amount' => $previousTotalAmount,
              ]
            );
          }
        } else {
          if ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
            $installments = [];
            $runningPercent = 0.0;
            $count = count($customSchedule);
            foreach ($customSchedule as $index => $item) {
              $amount = round((float) ($item['amount'] ?? 0), 2);
              $percentage = $totalAmount > 0
                ? round(($amount / $totalAmount) * 100, 2)
                : 0;

              if ($index === $count - 1 && $totalAmount > 0) {
                $percentage = round(100 - $runningPercent, 2);
              }

              $runningPercent += $percentage;
              $installments[] = [
                'label' => trim((string) ($item['label'] ?? '')),
                'percentage' => $percentage,
                'amount' => $amount,
              ];
            }
          } else {
            $scheduleItems = PaymentScheduleTemplates::itemsFor($scheduleType);
            $installments = PaymentScheduleCalculator::withAmounts($scheduleItems, $totalAmount);
          }

          $beforeScheduleType = $existingSchedule?->schedule_type;
          $beforeTotalAmount = $existingSchedule ? (float) $existingSchedule->total_amount : null;
          $beforeInstallments = $existingSchedule
            ? $existingSchedule->installments
              ->sortBy('position')
              ->values()
              ->map(fn ($item) => [
                'label' => $item->label,
                'percentage' => round((float) $item->percentage, 2),
                'amount' => round((float) $item->amount, 2),
              ])->all()
            : [];

          $afterInstallments = collect($installments)
            ->map(fn ($item) => [
              'label' => $item['label'],
              'percentage' => round((float) $item['percentage'], 2),
              'amount' => round((float) $item['amount'], 2),
            ])
            ->values()
            ->all();

          $scheduleChanged =
            $beforeScheduleType !== $scheduleType
            || abs((float) ($beforeTotalAmount ?? 0) - $totalAmount) > 0.01
            || $beforeInstallments !== $afterInstallments;

          if ($scheduleChanged) {
            if (!$existingSchedule) {
              $existingSchedule = PaymentSchedule::create([
                'order_id' => $order->id,
                'schedule_type' => $scheduleType,
                'total_amount' => $totalAmount,
              ]);
            } else {
              $existingSchedule->update([
                'schedule_type' => $scheduleType,
                'total_amount' => $totalAmount,
              ]);
              $existingSchedule->installments()->delete();
            }

            foreach ($installments as $index => $installment) {
              $existingSchedule->installments()->create([
                'position' => $index + 1,
                'label' => $installment['label'],
                'percentage' => $installment['percentage'],
                'amount' => $installment['amount'],
                'status' => 'PENDING',
              ]);
            }

            OrderFinancialEventLogger::log(
              $order,
              'PAYMENT_SCHEDULE_DEFINED',
              "Payment schedule configured as {$scheduleType}",
              [
                'schedule_type' => $scheduleType,
                'total_amount' => $totalAmount,
                'before_schedule_type' => $beforeScheduleType,
                'before_total_amount' => $beforeTotalAmount,
                'before_installments' => $beforeInstallments,
                'installments' => $afterInstallments,
              ]
            );
          }
        }
      }

      if (abs((float) $newAmount - (float) $oldAmount) > 0.01) {
        OrderFinancialEventLogger::log(
          $order,
          'PROJECT_AMOUNT_UPDATED',
          'Project amount updated',
          [
            'before_amount' => (float) $oldAmount,
            'after_amount' => (float) $newAmount,
          ]
        );
      }

      if ($request->has('change_order_enabled')) {
        $changeOrderEnabled = filter_var($request->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN);
        $changeOrderPayment = $order->orderPayments()->where('type', 'CHANGE_ORDER')->first();
        if ($changeOrderEnabled) {
          $payload = [
            'amount' => $request->input('change_order_amount') ?? 0,
            'note' => $request->input('change_order_note'),
          ];
          if ($changeOrderPayment) {
            $before = [
              'amount' => (float) $changeOrderPayment->amount,
              'note' => $changeOrderPayment->note,
              'status' => $changeOrderPayment->status,
            ];
            $changeOrderPayment->update($payload);
            if (abs((float) $before['amount'] - (float) ($changeOrderPayment->amount ?? 0)) > 0.01 || (string) $before['note'] !== (string) ($changeOrderPayment->note ?? '')) {
              OrderFinancialEventLogger::log(
                $order,
                'CHANGE_ORDER_UPDATED',
                'Change order payment updated',
                [
                  'order_payment_id' => $changeOrderPayment->id,
                  'before' => $before,
                  'after' => [
                    'amount' => (float) $changeOrderPayment->amount,
                    'note' => $changeOrderPayment->note,
                    'status' => $changeOrderPayment->status,
                  ],
                ]
              );
            }
          } else {
            $createdChangeOrder = $order->orderPayments()->create([
              'type' => 'CHANGE_ORDER',
              'status' => 'PENDING',
              ...$payload,
            ]);
            OrderFinancialEventLogger::log(
              $order,
              'CHANGE_ORDER_CREATED',
              'Change order payment created',
              [
                'order_payment_id' => $createdChangeOrder->id,
                'amount' => (float) $createdChangeOrder->amount,
                'note' => $createdChangeOrder->note,
                'status' => $createdChangeOrder->status,
              ]
            );
          }
        } elseif ($changeOrderPayment) {
          OrderFinancialEventLogger::log(
            $order,
            'CHANGE_ORDER_REMOVED',
            'Change order payment removed',
            [
              'order_payment_id' => $changeOrderPayment->id,
              'amount' => (float) $changeOrderPayment->amount,
              'note' => $changeOrderPayment->note,
              'status' => $changeOrderPayment->status,
            ]
          );
          $changeOrderPayment->delete();
        }
      }

      $currentWorkTeamNotes = trim((string) ($request->work_team_notes ?? ''));
      if ($currentWorkTeamNotes !== '') {
        $latestWorkTeamNote = $order->notes()
          ->where('type', 'work_team_note')
          ->latest()
          ->first();

        if (!$latestWorkTeamNote || trim((string) $latestWorkTeamNote->content) !== $currentWorkTeamNotes) {
          $order->notes()->create([
            'content' => $currentWorkTeamNotes,
            'type' => 'work_team_note',
            'user_id' => auth()->id(),
          ]);
        }
      }
      
      //dd($request->file('attachments'));
      if ($request->hasFile('attachments')) {
        $files = $request->file('attachments');
        foreach ($files as $file) {
          $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
          $filePath = $file->storeAs('order_files', $fileName, 'public');
          $order->attachments()->create([
            'filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => 'order_files',
            'user_id' => auth()->id(),
          ]);
        }
      }
      $order->installationTeams()->sync($request->installation_teams ?? []);
      $order->load('installationTeams');
     
      $order->owners()->sync($request->owners ?? []);
      $order->syncFrameColors($request->frame_color ?? []);
      $order->orderProducts()->delete();
      $orderProductsPayload = $request->orderProducts ?? [];
      foreach ($orderProductsPayload as $product) {
        $orderProduct = OrderProduct::create([
          'order_id' => $order->id,
          'product_config_id' => $product['product_config_id'],
          'type_of_work_id' => $product['type_of_work_id'],
          'height' => $product['height'],
          'width' => $product['width'],
          'qty' => $product['qty'],
          'unit_price' => $product['unit_price'],
          'total_price' => $product['total_price'],
          'unit_price_with_extraworks' => $product['unit_price_with_extraworks'],
          'total_price_with_extraworks' => $product['total_price_with_extraworks'],
          'extra_work_price' => $product['extra_work_price'],
          'notes' => $product['notes'],
          'storefront_area' => $product['storefront_area'],
          'installation_other_level' => $product['installation_other_level'],
          'product_category_id' => $product['product_category_id'],
          'type_of_product_id' => $product['type_of_product_id'],
          //'pivot_cost' => $product['pivot_cost'],
          'new_price_storefront' => $product['new_price_storefront'],
        ]);

        $extraWorks = [];
        $product_extra_works = $product['extra_works'] ?? [];

        for ($i = 0; $i < count($product_extra_works); $i++) {
          $extraWorks[$product_extra_works[$i]['extra_work_id']] = [
            'price' => $product_extra_works[$i]['price'],
            'amount' => $product_extra_works[$i]['amount'],
          ];
        }

        $orderProduct->orderProductExtraWorks()->attach($extraWorks);
      }

      DB::commit();
      if ($sendEmail || $order->is_send_email == 0) {
        $order->orderStatus()->create([
          'status' => $status,
          'user_id' => auth()->user()->id,
          'notes' => "$status created by " . auth()->user()->name,
          'start_date' => $request->installation_date,
          'end_date' => $request->installation_end_date,
          'pickup_date' => $request->delivery_date,
          'service_date' => $request->service_date,
          'complete_date' => $request->complete_date,
          'material_received_date' => $request->material_received_date,
        ]);

        if ($status === OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value) {
          event(new OrderStatusChanged($order, $status));
        }
        
        $this->sendEmail($order);
        $order->update([
          'is_send_email' => true,
        ]);
       
      }
      else if (($installationTeamsChanged || $supervisorChanged) && $request->status== OrderStatusEnum::CONFIRMED->value) {
            $order->update([
              'do_not_send_email' => true,
            ]);
            $this->sendEmail($order);
             //dd( $order->toArray());
        
      }

    } catch (\Throwable $th) {
       DB::rollback();
        throw  $th;
    }
  }

  public function partialUpdate(Request $request, Order $order)
  {
    $statusOrder = $order->status;

    if ($request->has('project_amount') && $order->hasReachedContractSigned()) {
      $incomingAmount = $request->input('project_amount');
      if ($incomingAmount !== null && $incomingAmount !== '' && abs((float) $incomingAmount - (float) ($order->project_amount ?? 0)) > 0.01) {
        throw ValidationException::withMessages([
          'project_amount' => 'Project amount cannot be edited after CONTRACT SIGNED BY CLIENT. Use Change Order instead.',
        ]);
      }
    }

    $order->loadMissing(['installationTeams']);
    $installer = $order->installationTeams()->pluck('installation_teams.id')->toArray();
    $currentInstallers = array_map('intval', $installer);
    $requestInstallers = array_map('intval', $request->installation_teams ?? []);

    sort($currentInstallers);
    sort($requestInstallers);

    $installationTeamsChanged = $currentInstallers !== $requestInstallers;
    $supervisorChanged = ((int) $order->supervisor_id !== (int) $request->supervisor_id);

    $order->update($request->except('installation_teams', 'supervisor_payment_status'));
    if ($request->status == OrderStatusEnum::COMPLETE->value) {
      $supervisor_payment_status = SupervisorPaymentStatusEnum::PENDING->value;
    } else {
      $supervisor_payment_status = $order->supervisor_payment_status;
    }
    $order->installationTeams()->sync($request->installation_teams);
    $order->load('installationTeams');


    $installer = $order->installationTeams()->count();
    $orderExtraFields = $order->paymentExtraFields()->count() ?? 0;
    $order->update(['supervisor_payment_status' => $supervisor_payment_status]);
    //dd($request->file('walk_trough_attach'));
    if ($request->hasFile('attachments')) {
      $files = $request->file('attachments');
      foreach ($files as $file) {
        $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('order_files', $fileName, 'public');
        $order->attachments()->create([
          'filename' => $file->getClientOriginalName(),
          'file_path' => $filePath,
          'file_type' => 'order_files',
          'user_id' => auth()->id(),
        ]);
      }
    }
    if ($request->hasFile('walk_trough_attach')) {
      $file = $request->file('walk_trough_attach');

      $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
      $filePath = $file->storeAs('order_files', $fileName, 'public');
      $order->attachments()->create([
        'filename' => $file->getClientOriginalName(),
        'file_path' => $filePath,
        'file_type' => 'walk_trough_attach',
        'user_id' => auth()->id(),
      ]);
    }
    if ($request->hasFile('pre_inspection_attach')) {
      $file = $request->file('pre_inspection_attach');

      $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
      $filePath = $file->storeAs('order_files', $fileName, 'public');
      $order->attachments()->create([
        'filename' => $file->getClientOriginalName(),
        'file_path' => $filePath,
        'file_type' => 'pre_inspection_attach',
        'user_id' => auth()->id(),
      ]);
    }

    if ($request->hasFile('inspection_attach')) {
      $file = $request->file('inspection_attach');

      $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
      $filePath = $file->storeAs('order_files', $fileName, 'public');
      $order->attachments()->create([
        'filename' => $file->getClientOriginalName(),
        'file_path' => $filePath,
        'file_type' => 'inspection_attach',
        'user_id' => auth()->id(),
      ]);
    }



    $sendEmail = $request->status != $statusOrder;
   
    if ($sendEmail && $request->is_send_email == 0) {

      $order->orderStatus()->create([
        'status' => $request->status,
        'user_id' => auth()->user()->id,
        'notes' => $request->status . " created by " . auth()->user()->name,
        'start_date' => $request->installation_date,
        'end_date' => $request->installation_end_date,
        'pickup_date' => $request->delivery_date,
        'inspection_date' => $request->inspection_date,
        'finish_date' => $request->finish_date,
        'final_inspection_date' => $request->final_inspection_date,
        'service_date' => $request->service_date,
        'complete_date' => $request->complete_date,
        'material_received_date' => $request->material_received_date,
      ]);

      if ($request->status === OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value) {
        event(new OrderStatusChanged($order, $request->status));
      }

      $this->sendEmail($order);
      //$this->whatsapp($order);
    }  else if (($installationTeamsChanged || $supervisorChanged) && $request->status== OrderStatusEnum::CONFIRMED->value) {
      $order->update([
        'do_not_send_email' => true,
      ]);
      $this->sendEmail($order);
  
    }else {
      $orderStatus = $order->orderStatus()->where('status', $request->status)->first(); // Busca el registro relacionado

      if ($orderStatus) {
        // Actualiza el registro existente (solo cambia user_id si cambia el status)
        $payload = [
          //'status' => $request->status,
          //'notes' => $request->status . " updated by " . auth()->user()->name,
          'start_date' => $request->installation_date,
          'end_date' => $request->installation_end_date,
          'pickup_date' => $request->delivery_date,
          'inspection_date' => $request->inspection_date,
          'finish_date' => $request->finish_date,
          'service_date' => $request->service_date,
          'final_inspection_date' => $request->final_inspection_date,
          'complete_date' => $request->complete_date,
          'material_received_date' => $request->material_received_date,
        ];

        if ($sendEmail) {
          $payload['user_id'] = auth()->user()->id;
        }

        $orderStatus->update($payload);
      }
    }
  }
}
