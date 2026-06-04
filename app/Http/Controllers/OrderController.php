<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\Actions\UpdateOrder;
use App\Enum\AttachmentsFileTypeEnum;
use App\Enum\FrameColorEnum;
use App\Enum\MethodOfPayment;
use App\Http\Resources\OrderCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\StatusUserEnum;
use App\Enum\TypeOfFinancing;
use App\Http\Requests\PartialOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\DurationOfWork;
use App\Models\ExtraWork;
use App\Models\InstallationTeam;
use App\Models\OrderStatus;
use App\Models\PaymentExtraField;
use App\Models\ProductCategory;
use App\Models\ProductConfig;
use App\Models\ProductCost;
use App\Models\TravelCost;
use App\Models\TypeOfHousing;
use App\Models\TypeOfProduct;
use App\Models\TypeOfWork;
use App\Models\User;
use App\Traits\OrderDates;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Doctrine\DBAL\Types\Type;
use App\Support\PaymentInstallmentPresenter;
use App\Support\PaymentScheduleTemplates;

class OrderController extends Controller
{

  use OrderDates;
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    /*return Inertia::render('Order/Index', [
      'orders' => new OrderCollection(
        Order::with(['installationTeams.user'])->filter($request->only(['text', 'status']))
          ->orderBy('orders.updated_at', 'desc')
          ->orderBy('orders.id', 'desc')
          ->paginate()
          ->withQueryString()
      ),
      'statuses' => [
        OrderStatusEnum::REVIEW->value,
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::ON_HOLD->value,
        OrderStatusEnum::RESCHEDULE->value,
        OrderStatusEnum::MATERIALS_RECEIVED->value,
      ]
    ]);*/
     $allowedStatuses = [
        OrderStatusEnum::REVIEW->value,
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::REPLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::DELIVERY_CONFIRMED->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::ON_HOLD->value,
        OrderStatusEnum::RESCHEDULE->value,
        OrderStatusEnum::MATERIALS_RECEIVED->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::CANCELED->value
    ];

    $filters = $request->only(['text', 'status']);
    $filters['is_supply'] = $request->boolean('is_supply');

    $orders = Order::with(['installationTeams.user'])
        ->whereIn('orders.status', $allowedStatuses)   // <- filtro duro por status permitidos
        ->filter($filters)   // si viene un status fuera de la lista, igual quedará excluido
        ->orderBy('orders.updated_at', 'desc')
        ->orderBy('orders.id', 'desc')
        ->paginate()
        ->withQueryString();

    return Inertia::render('Order/Index', [
        'orders'   => new OrderCollection($orders),
        'statuses' => $allowedStatuses, // reutiliza los mismos para el frontend (select/filtros)
    ]);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  protected function getOrderFormData(array $services = null): array
  {
    return [
      'clients' => Client::with(['companyContact:id,name,email'])->get(),
      'type_of_works' => TypeOfWork::all(),
      'types_of_housing' => TypeOfHousing::all(),
      'owners' => User::role(RoleEnum::OWNER->value)
        ->where('status', StatusUserEnum::ACTIVE->value)
        ->get(),
      'installation_teams' => InstallationTeam::with(['user', 'typeHousing'])
        ->whereHas('user', function ($query) {
          $query->where('status', StatusUserEnum::ACTIVE->value);
        })
        ->get(),
      'supervisors' => User::role(RoleEnum::SUPERVISOR->value)
        ->where('status', StatusUserEnum::ACTIVE->value)
        ->get(),
      'methods_of_payment' => [
        MethodOfPayment::CASH->value,
        MethodOfPayment::FINANCED->value,
        MethodOfPayment::FINANCEDCASH->value,
        MethodOfPayment::AIA->value,
      ],
      'type_of_financing' => [
        TypeOfFinancing::WELLS_FARGO->value,
        TypeOfFinancing::SUN_LIGHT->value,
        TypeOfFinancing::HOME_RUN->value,
        TypeOfFinancing::YGREEN->value,
        TypeOfFinancing::SLIN->value,
        TypeOfFinancing::GOOD_LEAP->value,
      ],
      'services' => $services ?? [
        ServiceEnum::INSTALLATION->value,
        ServiceEnum::DELIVERY->value,
        ServiceEnum::PICKUP->value,
      ],
      'frame_colors' => [
        FrameColorEnum::WHITE->value,
        FrameColorEnum::BLACK->value,
        FrameColorEnum::BRONZE->value,
        FrameColorEnum::CLEAR_ANODIZED->value,
        FrameColorEnum::OTHERS->value
      ],
      'travel_costs' => TravelCost::all(),
      'duration_of_works' => DurationOfWork::all(),
      // 'products_config' => ProductConfig::all(),

      'products_config' => ProductConfig::where(function ($query) {
        $query->where('name', 'Mullion')
          ->whereHas('productCategory', function ($categoryQuery) {
            $categoryQuery->where('name', 'Mullion')
              ->whereHas('typeOfProduct', function ($typeQuery) {
                $typeQuery->where('name', 'Mullion');
              });
          })
          ->orWhereDoesntHave('productCategory'); // Incluir configuraciones sin categoría "Mullion"
      })->orWhere(function ($query) {
        $query->whereHas('productCategory', function ($categoryQuery) {
          $categoryQuery->where('name', '!=', 'Mullion'); // Categorías distintas de "Mullion"
        });
      })->get(),
      'type_of_products' => TypeOfProduct::with(['extraWorks'])->get(),
      'product_category' => ProductCategory::all(),
      'extra_works' => ExtraWork::all(),
      'extraWorks' => ExtraWork::select('id', 'name')->get(),
      'product_costs' => ProductCost::all(),
      'payment_schedule_types' => PaymentScheduleTemplates::types(),
      'payment_schedule_templates' => PaymentScheduleTemplates::templates(),
      'order_types' => [
        OrderTypeEnum::RESIDENTIAL->value,
        OrderTypeEnum::COMMERCIAL->value,
        OrderTypeEnum::SUPPLY->value,
      ],
      'status' => [
        OrderStatusEnum::REVIEW->value,
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::DELIVERY_CONFIRMED->value,
      ]
    ];
  }

  public function create()
  {
    return Inertia::render('Order/Create', $this->getOrderFormData());
  }

  public function createService()
  {
    $data = $this->getOrderFormData([ServiceEnum::SERVICE->value]);
    $data['defaultService'] = ServiceEnum::SERVICE->value;
    $data['status'] = [
      OrderStatusEnum::PLANNED->value,
      OrderStatusEnum::CONFIRMED->value,
      OrderStatusEnum::EXECUTION->value,
      OrderStatusEnum::SUPERVISION->value,
      OrderStatusEnum::FINAL_COLLECT->value,
      OrderStatusEnum::COMPLETE->value,
    ];
    $data['supervisors'] = $data['supervisors']->map(function ($supervisor) {
      return [
        'id' => $supervisor->id,
        'name' => $supervisor->name,
      ];
    })->values();

    return Inertia::render('Order/CreateService', $data);
  }

  public function getDeliveryAndInstallationDate($payment_factory_date, $type_of_housing, $county_id, $service, $hasPermit = false)
  {
    $estimate_eta_date = $this->estimateETADate($payment_factory_date);
    $estimate_delivery_date = $this->getEstimateDeliveryDate($payment_factory_date, $service, $county_id, $type_of_housing, $hasPermit);
    $estimate_installation_date = $this->getEstimateInstallationDate($estimate_delivery_date, $service, $hasPermit);

    return response()->json([
      'estimate_eta_date' => $estimate_eta_date,
      'estimate_delivery_date' => $estimate_delivery_date,
      'estimate_installation_date' => $estimate_installation_date
    ]);
  }

  public function getDeliveryAndPickupDate($payment_factory_date)
  {
    $estimate_eta_date = $this->estimateETADate($payment_factory_date);
    $estimate_delivery_date = $this->getEstimateDeliveryByEtaDate($estimate_eta_date);


    return response()->json([
      'estimate_eta_date' => $estimate_eta_date,
      'estimate_delivery_date' => $estimate_delivery_date
    ]);
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
  */
  public function store(StoreOrderRequest $storeOrderRequest, CreateOrder $createOrder)
  {
    $createOrder->handle($storeOrderRequest);
    return redirect()->route('order.index')
      ->with('success', 'Order created successfully.');
  }

  public function storeService(StoreServiceRequest $storeServiceRequest, CreateOrder $createOrder)
  {
    $createOrder->handle($storeServiceRequest);

    return redirect()->route('order.index')
      ->with('success', 'Service created successfully.');
  }

  public function updateService(UpdateServiceRequest $updateServiceRequest, UpdateOrder $updateOrder, Order $order)
  {
    // \\Log::info('UpdateService payload', $updateServiceRequest->all());
    $updateOrder->handle($updateServiceRequest, $order);

    return redirect()->route('order.index')
      ->with('success', 'Service updated successfully.');
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function edit(Order $order)
  {
    if ($order->service === ServiceEnum::SERVICE->value) {
      $data = $this->getOrderFormData([ServiceEnum::SERVICE->value]);
      $data['defaultService'] = ServiceEnum::SERVICE->value;
      $data['status'] = [
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::REPLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::COMPLETE->value,
      ];
      $data['supervisors'] = $data['supervisors']->map(function ($supervisor) {
        return [
          'id' => $supervisor->id,
          'name' => $supervisor->name,
        ];
      })->values();

      $loadedOrder = $order->load([
        'client.companyContact',
        'installationTeams.user',
        'orderProducts.productConfig',
        'orderProducts.productCategory',
        'orderProducts.orderProductExtraWorks',
        'orderProducts.typeOfWork',
        'paymentSchedule.installments.paidBy',
        'paymentSchedule.installments.movements.paidBy',
        'changeOrderPayment',
        'owners',
        'attachments',
        'attachmentRoleTargets',
      ]);
      $orderData = $loadedOrder->toArray();
      $orderData['attachment_role_targets_by_role'] = $this->attachmentRoleTargetsByRole($loadedOrder);
      $orderData['payment_schedule'] = PaymentInstallmentPresenter::schedule($loadedOrder->paymentSchedule);
      $orderData['has_contract_signed'] = $loadedOrder->hasReachedContractSigned();
      $orderData['replanned_reasons'] = $this->currentReplannedReasons($loadedOrder);
      $data['order'] = $orderData;

      return Inertia::render('Order/EditService', $data);
    }

    $status = [
      OrderStatusEnum::REVIEW->value,
      OrderStatusEnum::PLANNED->value,
      OrderStatusEnum::MATERIALS_RECEIVED->value,
      OrderStatusEnum::REPLANNED->value,
      OrderStatusEnum::CONFIRMED->value,
      OrderStatusEnum::ON_HOLD->value,
      OrderStatusEnum::COMPLETE->value,
    ];

    if ($order->service === ServiceEnum::INSTALLATION->value) {
      $status = [
        OrderStatusEnum::REVIEW->value,
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::REPLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::DELIVERY_CONFIRMED->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::ON_HOLD->value,
        OrderStatusEnum::MATERIALS_RECEIVED->value,
        OrderStatusEnum::CANCELED->value,

      ];
      if ($order->status === OrderStatusEnum::CONFIRMED->value) {
        $status[] = OrderStatusEnum::RESCHEDULE->value;
      }
    }
    $statusPaymentInstaller = PaymentExtraField::where('order_id', $order->id)->first();
    //dd($statusPaymentInstaller->installer_payment_status);

    $loadedOrder = $order->load([
        'client.companyContact',
        'typeOfWork',
        'typeOfHousing',
        'user',
        'attachments',
        'attachmentRoleTargets',
        'owners',
        'orderProducts.orderProductExtraWorks',
        'orderColors',
        'paymentSchedule.installments.paidBy',
        'paymentSchedule.installments.movements.paidBy',
        'changeOrderPayment',

        'installationTeams.user',
      ]);
    $orderData = $loadedOrder->toArray();
    $orderData['attachment_role_targets_by_role'] = $this->attachmentRoleTargetsByRole($loadedOrder);
    $orderData['payment_schedule'] = PaymentInstallmentPresenter::schedule($loadedOrder->paymentSchedule);
    $orderData['has_contract_signed'] = $loadedOrder->hasReachedContractSigned();
    $orderData['replanned_reasons'] = $this->currentReplannedReasons($loadedOrder);

    return Inertia::render('Order/Edit', [
      'order' => $orderData,
      //dd($order),
      'clients' => Client::with(['companyContact:id,name,email'])->get(),

      'extraWorks' => ExtraWork::select('id', 'name')->get(),
      
      //'statusPaymentInstaller' => $statusPaymentInstaller->installer_payment_status,
      'statusPaymentInstaller' => $statusPaymentInstaller
    ? $statusPaymentInstaller->installer_payment_status
    : 'OPEN',
      'type_of_works' => TypeOfWork::all(),
      'types_of_housing' => TypeOfHousing::all(),
      /*'owners' => User::role(RoleEnum::OWNER->value)->get(),
      'installation_teams' => InstallationTeam::with(['user', 'typeHousing'])->get(),
      'supervisors' => User::role(RoleEnum::SUPERVISOR->value)->get(),*/
      'owners' => User::role(RoleEnum::OWNER->value)
        ->where('status', StatusUserEnum::ACTIVE->value)
        ->get(),
      'installation_teams' => InstallationTeam::with(['user', 'typeHousing'])
        ->whereHas('user', function ($query) {
          $query->where('status', StatusUserEnum::ACTIVE->value);
        })
        ->get(),
      'supervisors' => User::role(RoleEnum::SUPERVISOR->value)
        ->where('status', StatusUserEnum::ACTIVE->value)
        ->get(),
      'methods_of_payment' => [
        MethodOfPayment::CASH->value,
        MethodOfPayment::FINANCED->value,
        MethodOfPayment::FINANCEDCASH->value,
        MethodOfPayment::AIA->value,
      ],
      'type_of_financing' => [
        TypeOfFinancing::WELLS_FARGO->value,
        TypeOfFinancing::SUN_LIGHT->value,
        TypeOfFinancing::HOME_RUN->value,
        TypeOfFinancing::YGREEN->value,
        TypeOfFinancing::SLIN->value,
        TypeOfFinancing::GOOD_LEAP->value,
      ],
      'frame_colors' => [
        FrameColorEnum::WHITE->value,
        FrameColorEnum::BLACK->value,
        FrameColorEnum::BRONZE->value,
        FrameColorEnum::CLEAR_ANODIZED->value,
        FrameColorEnum::OTHERS->value
      ],
      'services' => [
        ServiceEnum::INSTALLATION->value,
        ServiceEnum::DELIVERY->value,
        ServiceEnum::PICKUP->value
      ],
      'travel_costs' => TravelCost::all(),
      'duration_of_works' => DurationOfWork::all(),
      'products_config' => ProductConfig::all(),
      'type_of_products' => TypeOfProduct::with(['extraWorks'])->get(),
      'product_category' => ProductCategory::all(),
      'product_costs' => ProductCost::all(),
      'payment_schedule_types' => PaymentScheduleTemplates::types(),
      'payment_schedule_templates' => PaymentScheduleTemplates::templates(),
      'order_types' => [
        OrderTypeEnum::RESIDENTIAL->value,
        OrderTypeEnum::COMMERCIAL->value,
        OrderTypeEnum::SUPPLY->value,
      ],
      'status' => $status
    ]);
   
  }

  private function attachmentRoleTargetsByRole(Order $order): array
  {
    $order->loadMissing('attachmentRoleTargets');

    return $order->attachmentRoleTargets
      ->groupBy('role')
      ->map(function ($items) {
        return $items->pluck('attachment_id')
          ->map(fn ($id) => (int) $id)
          ->unique()
          ->values()
          ->all();
      })
      ->toArray();
  }

  private function currentReplannedReasons(Order $order): array
  {
    if ($order->status !== OrderStatusEnum::REPLANNED->value) {
      return [];
    }

    $statusRecord = $order->orderStatus()
      ->where('status', OrderStatusEnum::REPLANNED->value)
      ->latest('id')
      ->first();

    return is_array($statusRecord?->replanned_reasons) ? $statusRecord->replanned_reasons : [];
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(UpdateOrderRequest $updateOrderRequest, UpdateOrder $updateOrder, Order $order)
 {         
    $updateOrder->handle($updateOrderRequest, $order);
    return redirect()->route('order.index')
      ->with('success', 'Order updated successfully.');
  }

  public function updateFromModal(PartialOrderRequest $request, UpdateOrder $updateOrder, Order $order)
  {
    if ($this->isRestrictedOwner(auth()->user()) && !$order->isAccessibleToOwner(auth()->user())) {
      abort(403, 'You are not authorized to update this order.');
    }

    if ($request->user()->hasRole(RoleEnum::SUPERVISOR->value)) {
      $request->merge([
        'installation_date' => $order->installation_date,
        'installation_end_date' => $order->installation_end_date,
      ]);
    }

    $updateOrder->partialUpdate($request, $order);

    return redirect()->route('dashboard')
      ->with('success', 'Order updated successfully.');
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy(Order $order)
  {
    $order->delete();
    return redirect()
      ->back()
      ->with('success', 'Order deleted successfully.');
  }

  public function storeAttachment(Request $request, Order $order)
  {
    $validated = $request->validate([
      'attachments' => ['required', 'array'],
      'attachments.*' => ['file', 'max:10240'],
    ]);

    if ($request->hasFile('attachments')) {
      foreach ($request->file('attachments') as $file) {
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

    $order->load('attachments.user');

    return response()->json([
      'attachments' => $order->attachments->map(fn ($attachment) => [
        'id' => $attachment->id,
        'filename' => $attachment->filename,
        'file_path' => $attachment->file_path,
        'file_type' => $attachment->file_type,
        'created_at' => optional($attachment->created_at)->toIso8601String(),
        'uploaded_by' => $attachment->user?->name,
        'user_id' => $attachment->user_id,
      ])->values(),
      'message' => 'Attachments uploaded successfully.'
    ], 201);
  }

  public function dropAttachment(Request $request, $id)
  {
    $attachment = Attachment::find($id);

    if (!$attachment) {
      if ($request->expectsJson()) {
        return response()->json(['message' => 'Attachment not found.'], 404);
      }

      return redirect()
        ->back()
        ->with('error', 'Attachment not found');
    }

    $user = auth()->user();

    $canDelete = $user && (
      $attachment->user_id === $user->id ||
      $user->hasRole([
        RoleEnum::ADMIN->value,
        RoleEnum::ACCOUNT_MANAGER->value,
        RoleEnum::OWNER_ADMIN->value,
      ])
    );

    if (!$canDelete) {
      if ($request->expectsJson()) {
        return response()->json(['message' => 'You do not have permission to delete this file.'], 403);
      }

      return redirect()
        ->back()
        ->with('error', 'You do not have permission to delete the file.');
    }

    $attachment->delete();

    if ($request->expectsJson()) {
      return response()->json(['message' => 'Attachment deleted.']);
    }

    return redirect()
      ->back()
      ->with('success', 'Attachment deleted successfully.');
  }

  public function updateDatePaid(Request $request)
  {
    $order = Order::find($request->order_id);
    $order->supervisor_payment_date = $request->date_paid;
    $order->supervisor_payment_status = SupervisorPaymentStatusEnum::CLOSED->value;
    $order->save();
  }

  public function updateStatusPayment(Request $request)
{
    $order = Order::find($request->order_id);
    $order->supervisor_payment_status = $request->status;
    $order->save();

    return response()->json(['success' => true]);
}

      public function supervisorCloseAll(Request $request)
      {   
            //dd($request->all());
            foreach ($request->order_ids as $orderId) {
              $order = Order::find($orderId);

              $order->supervisor_payment_date = $request->payment_date;
              $order->supervisor_payment_status = 'CLOSED';

              $order->save();
          }

          //return response()->json(['message' => 'Payment completed successfully.']);
              
              return redirect()->route('report.show_supervisor', $request->supervisor_id)
              ->with('success', 'Order updated successfully.');
      }

  public function statusOrder($id)
  { // Obtener las órdenes por supervisor
    $order = Order::find($id);
    $orderStatuses = OrderStatus::where('order_id', $id)
      ->with(['order', 'user'])
      ->get();

    // Obtener los parámetros de filtro de la solicitud (request)
    return Inertia::render('Order/ShowStatusOrder', [
      //'orderStatuses' => $orderStatuses,
      'order' => $order,
      'orderStatuses' => $orderStatuses->map(function ($status) {
        return [
          ...$status->toArray(),
          'created_at_formatted' => Carbon::parse($status->created_at)
            ->setTimezone('America/New_York')
            ->format('Y-m-d'),
        ];
      }),


    ]);
  }

  public function duplicate($id)
  {
    $message = 'Estimate duplicated successfully.';
    $estimate = Order::with(['client'])->findOrFail($id);

    // Duplicar la orden principal
    $newEstimate = $estimate->replicate();
    $newEstimate->name = $newEstimate->name . ' (copy)';
    $newEstimate->user_id = auth()->user()->id;
    $newEstimate->status = OrderStatusEnum::PLANNED->value;
    $newEstimate->is_send_email = false;
    $newEstimate->pre_inspection = false;
    $newEstimate->inspection = false;
    $newEstimate->walk_trough = false;
    $newEstimate->project_amount= 0;
    // Reuse the same client to avoid creating duplicates on order duplication.
    $newEstimate->client_id = $estimate->client_id;
    $newEstimate->push(); // Guardar la nueva orden duplicada

    // Duplicar los OrderProducts asociados
    foreach ($estimate->orderProducts as $orderProduct) {
      $newOrderProduct = $orderProduct->replicate();
      $newOrderProduct->order_id = $newEstimate->id;
      $newOrderProduct->save();

      // Duplicar las relaciones con OrderProductExtraWorks
      foreach ($orderProduct->orderProductExtraWorks as $extraWork) {
        $newOrderProduct->orderProductExtraWorks()->attach($extraWork->id, [
          'price' => $extraWork->pivot->price,
          'amount' => $extraWork->pivot->amount,
          'created_at' => now(),
          'updated_at' => now(),
        ]);
      }
    }

    $owners = $estimate->owners->pluck('id')->toArray(); // Obtener los IDs de los owners
    $newEstimate->owners()->attach($owners);

    // Duplicar los InstallationTeams (relación BelongsToMany)
    $installationTeams = $estimate->installationTeams->pluck('id')->toArray(); // Obtener los IDs de los equipos de instalación
    $newEstimate->installationTeams()->attach($installationTeams); // Asociar los mismos equipos al nuevo estimate

    // Duplicar los Attachments (relación MorphMany)
    foreach ($estimate->attachments->where('file_type', AttachmentsFileTypeEnum::ORDER_FILES->value) as $attachment) {
      $newAttachment = $attachment->replicate();
      $newAttachment->attachable_id = $newEstimate->id;
      $newAttachment->attachable_type = get_class($newEstimate);
      $newAttachment->save();
    }

    return redirect()
      ->route('order.index')
      ->with('success', $message);
  }

  private function isRestrictedOwner(?User $user): bool
  {
    if (!$user) {
      return false;
    }

    return $user->hasRole(RoleEnum::OWNER->value) && !$user->hasAnyRole([
      RoleEnum::ADMIN->value,
      RoleEnum::ACCOUNT_MANAGER->value,
      RoleEnum::OWNER_ADMIN->value,
      RoleEnum::FRONTDESK_ADMIN->value,
      'FRONTDESK_ADMIN',
      'frondesk_admin',
      'frondestk_admin',
      'FRONDESK_ADMIN',
      'FRONDESTK_ADMIN',
    ]);
  }
}
