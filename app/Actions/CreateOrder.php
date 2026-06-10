<?php

namespace App\Actions;

use App\Enum\PlaningDateSupervisorEnum;
use App\Enum\MethodOfPayment;
use App\Enum\PaymentScheduleTypeEnum;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentSchedule;
use App\Models\SupervisorComissionOrder;
use App\Support\OrderClientEmailDeliveryLogger;
use App\Support\OrderClientEmailManager;
use App\Support\OrderFinancialEventLogger;
use App\Support\PaymentScheduleCalculator;
use App\Support\PaymentScheduleTemplates;
use App\Traits\ComissionSupervisor;
use App\Traits\OrderEmails;
use App\Traits\OrderStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrder
{

  use OrderEmails, OrderStatus, ComissionSupervisor;

  public function __construct(
    protected OrderClientEmailManager $orderClientEmailManager,
    protected OrderClientEmailDeliveryLogger $orderClientEmailDeliveryLogger
  ) {
  }
 
  public function handle(Request $request)
  {
    DB::transaction(function () use ($request) {
      if ($request->filled('client_id')) {
        $client = Client::with('companyContact')->findOrFail($request->client_id);
      } else {
        $client = Client::create([
          'name' => $request->client_name,
          'phone' => $request->phone,
          'email' => $request->email,
          'vip_clients' => $request->vip_clients,
          'vip_notes' => $request->vip_notes,
          'contact_type' => $request->contact_type,
          'user_id' => auth()->user()->id,
        ]);
      }

      $clientEmailSelection = (string) ($request->input('client_email_selection')
        ?: ($request->boolean('do_not_send_email')
          ? OrderClientEmailManager::NONE_SELECTION
          : OrderClientEmailManager::PRIMARY_SELECTION));
      $selectionError = $this->orderClientEmailManager->validateSelectionForContext(
        $client,
        $clientEmailSelection,
        $client->companyContact
      );
      if ($selectionError !== null) {
        throw ValidationException::withMessages([
          'client_email_selection' => $selectionError,
        ]);
      }

      $project_amount = $request->project_amount;

      if ($request->service == ServiceEnum::INSTALLATION->value || $request->service == ServiceEnum::INSTALLATION_ONLY->value || $request->service == ServiceEnum::SERVICE->value) {
        if ($request->type_of_housing_id == 3) {
          $execution_planing_date = PlaningDateSupervisorEnum::COMMERCIAL_PROJECTS->value;
        } else if ($request->city_permits == 1) {
          $execution_planing_date = PlaningDateSupervisorEnum::PROJECTS_WITH_PERMISSIONS->value;
        } else {
          $execution_planing_date = PlaningDateSupervisorEnum::PROJECTS_WITHOUT_PERMISSIONS->value;
        }

        if ($project_amount > 0) {
          $comissions = $this->ComissionSupervisor($project_amount);
          $totalCommission = array_sum(array_column($comissions, 'amount'));
        } else {
          $comissions = [];
          $totalCommission = 0.00;
        }

        $supervisor_payment_status = SupervisorPaymentStatusEnum::OPEN->value;
      } else {
        $execution_planing_date = 0;
        $totalCommission = 0.00;
        $supervisor_payment_status = null;
      }

      if ($request->city_permits) {
        $initial_payment_percentage = 80.00;
      } else {
        $initial_payment_percentage = 100.00;
      }
     
      $status = $request->status;
      $typeOfWorkId = $request->type_of_work_id ?: null;
      $typeOfHousingId = $request->type_of_housing_id ?: null;
      $travelCostId = $request->travel_cost_id ?: null;
      $durationOfWorkId = $request->duration_of_work_id ?: null;

      $order = Order::create([
        'client_id' => $client->id,
        'user_id' => auth()->user()->id,
        'name' => $request->name,
        'job_address' => $request->job_address,
        // 'job_city' => $request->job_city ?? $request->city,
        'order_number' => $request->order_number,
        'invoice_number' => $request->invoice_number,
        'order_type' => $request->order_type,
        'product_line' => $request->product_line,
        'type_of_work_id' => $typeOfWorkId,
        'type_of_housing_id' => $typeOfHousingId,
        'supervisor_id' => $request->supervisor_id,
        'travel_cost_id' => $travelCostId,
        'duration_of_work_id' => $durationOfWorkId,
        'method_of_payment' => $request->method_of_payment,
        'type_of_financing' => $request->type_of_financing,
        'service' => $request->service,
        'contract_signing_date' => $request->contract_signing_date,
        'payment_factory_date' => $request->payment_factory_date,
        'entry_date' => $request->entry_date,
        'eta_date' => $request->eta_date,
        'installation_end_date' => $request->installation_end_date,
        'additional_travel_costs' => $request->additional_travel_costs,
        'city_permits' => $request->city_permits,
        'city' => $request->city,
        'job_state' => $request->job_state,
        'job_zip' => $request->job_zip,
        'association_permits' => $request->association_permits,
        'equipment_rental' => $request->equipment_rental,
        'notes' => $request->notes,
        'work_team_notes' => null,
        'delivery_date' => $request->delivery_date,
        'installation_date' => $request->installation_date,
        'status' => $status,
        'cost_delivery' => $request->cost_delivery,
        'cost_city_fee' => $request->cost_city_fee,
        'project_amount' => $request->project_amount,
        'down_payment' => $request->method_of_payment === MethodOfPayment::FINANCEDCASH->value
          ? $request->down_payment
          : null,
        'initial_payment_percentage' => $initial_payment_percentage,
        'payment_definition' => $request->payment_definition,
        'execution_planing_date' => $execution_planing_date,
        'supervisor_payment_status' => $supervisor_payment_status,
        'do_not_send_email' => $request->do_not_send_email,
        'is_new_travel_cost' => $request->is_new_travel_cost,
        'new_travel_cost' => $request->new_travel_cost,
        'supervisor_commissions' => $totalCommission,
        'is_send_email' => true,
        'area' => $request->area,
      ]);
      $this->orderClientEmailManager->applySelection($order, $clientEmailSelection);
      $order->save();

      $paymentScheduleType = $request->payment_schedule_type;
      $isCashAndFinanced = $request->method_of_payment === MethodOfPayment::FINANCEDCASH->value;
      $requiresSchedule = in_array(
        $request->method_of_payment,
        [MethodOfPayment::CASH->value, MethodOfPayment::FINANCEDCASH->value],
        true
      );
      if ($requiresSchedule && $paymentScheduleType) {
        $totalAmount = $isCashAndFinanced
          ? (float) ($request->down_payment ?? 0)
          : (float) ($request->project_amount ?? 0);
        if ($paymentScheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
          $customSchedule = $request->input('custom_schedule', []);
          $installments = [];
          $runningPercent = 0.0;
          $count = count($customSchedule);

          foreach ($customSchedule as $index => $item) {
            $amount = round((float) ($item['amount'] ?? 0), 2);
            $percentage = $totalAmount > 0
              ? round(($amount / $totalAmount) * 100, 2)
              : 0.0;

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
          $scheduleItems = PaymentScheduleTemplates::itemsFor($paymentScheduleType);
          $installments = PaymentScheduleCalculator::withAmounts($scheduleItems, $totalAmount);
        }

        $paymentSchedule = PaymentSchedule::create([
          'order_id' => $order->id,
          'schedule_type' => $paymentScheduleType,
          'total_amount' => $totalAmount,
        ]);

        foreach ($installments as $index => $installment) {
          $paymentSchedule->installments()->create([
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
          "Payment schedule configured as {$paymentScheduleType}",
          [
            'schedule_type' => $paymentScheduleType,
            'total_amount' => $totalAmount,
            'installments' => $installments,
          ]
        );
      }

      $changeOrderEnabled = filter_var($request->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN);
      if ($changeOrderEnabled) {
        $changeOrderAmount = $request->input('change_order_amount');
        $changeOrderNote = $request->input('change_order_note');
        $payment = $order->orderPayments()->create([
          'type' => 'CHANGE_ORDER',
          'amount' => $changeOrderAmount ?? 0,
          'note' => $changeOrderNote,
          'status' => 'PENDING',
        ]);

        OrderFinancialEventLogger::log(
          $order,
          'CHANGE_ORDER_CREATED',
          'Change order payment created',
          [
            'order_payment_id' => $payment->id,
            'amount' => (float) $payment->amount,
            'note' => $payment->note,
            'status' => $payment->status,
          ]
        );
      }

      if ($request->filled('notes')) {
        $order->notes()->create([
          'content' => $request->notes,
          'type' => 'order_note',
          'user_id' => auth()->id(),
        ]);
      }

      $initialWorkTeamNotes = trim((string) ($request->work_team_notes ?? ''));
      if ($initialWorkTeamNotes !== '') {
        $order->notes()->create([
          'content' => $initialWorkTeamNotes,
          'type' => 'work_team_note',
          'user_id' => auth()->id(),
        ]);
      }

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

      if (!empty($comissions)) {
        foreach ($comissions as $comission) {
          SupervisorComissionOrder::create([
            'order_id' => $order->id,
            'percentage' => $comission['percentage'],
            'amount' => $comission['amount'],
            'tier' => $comission['tier'],
            'tier_base_amount' => $comission['tier_base_amount'],
          ]);
        }
      }

      $order->orderStatus()->create([
        'status' => $status,
        'user_id' => auth()->user()->id,
        'notes' => "$status created by " . auth()->user()->name,
        'start_date' => $request->installation_date,
        'end_date' => $request->installation_end_date,
        'pickup_date' => $request->delivery_date,
      ]);

      $installationTeams = $request->installation_teams ?? [];
      if (!empty($installationTeams)) {
        $order->installationTeams()->attach($installationTeams);
      }

      $owners = $request->owners ?? [];
      if (!empty($owners)) {
        $order->owners()->attach($owners);
      }
      $order->syncFrameColors($request->frame_color ?? []);

      $orderProductsPayload = $request->orderProducts ?? [];

      foreach ($orderProductsPayload as $product) {
        $typeOfWorkId = $product['type_of_work_id'] ?? null;
        if ($typeOfWorkId === 0 || $typeOfWorkId === '0' || $typeOfWorkId === '') {
          $typeOfWorkId = null;
        }

        $orderProduct = OrderProduct::create([
          'order_id' => $order->id,
          'product_config_id' => $product['product_config_id'],
          'type_of_work_id' => $typeOfWorkId,
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
          'pivot_cost' => $product['pivot_cost'],
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

      $order->load('client.companyContact', 'client.companyContacts');
      $this->orderClientEmailDeliveryLogger->logIfConfiguredDifferentlyFromDefault(
        $order,
        $client->email
      );
      $this->sendEmail($order);
     
      if (!$order) {
        throw new \Exception('Order not created');
      }
    });
  }
}
