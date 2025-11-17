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

class CreateOrder
{

  use OrderEmails, OrderStatus, ComissionSupervisor;
 
  public function handle(Request $request)
  {
    DB::transaction(function () use ($request) {
      $client = Client::create([
        'name' => $request->client_name,
        'phone' => $request->phone,
        'email' => $request->email,
        'vip_clients' => $request->vip_clients,
        'vip_notes' => $request->vip_notes,
        'contact_type' => $request->contact_type,
        'user_id' => auth()->user()->id,
      ]);

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
        'initial_payment_percentage' => $initial_payment_percentage,
        'payment_definition' => $request->payment_definition,
        'execution_planing_date' => $execution_planing_date,
        'supervisor_payment_status' => $supervisor_payment_status,
        'do_not_send_email' => $request->do_not_send_email,
        'is_new_travel_cost' => $request->is_new_travel_cost,
        'new_travel_cost' => $request->new_travel_cost,
        'supervisor_commissions' => $totalCommission,
        'is_send_email' => true,
        
      ]);

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

      $this->sendEmail($order);
     
      if (!$order) {
        throw new \Exception('Order not created');
      }
    });
  }
}
