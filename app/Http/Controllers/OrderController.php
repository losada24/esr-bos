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
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Http\Requests\PartialOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\DurationOfWork;
use App\Models\ExtraWork;
use App\Models\InstallationTeam;
use App\Models\OrderStatus;
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
use Doctrine\DBAL\Types\Type;

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
    return Inertia::render('Order/Index', [
      'orders' => new OrderCollection(
        Order::with(['installationTeams.user'])->filter($request->only(['text', 'status']))
          ->orderBy('orders.updated_at', 'desc')
          ->orderBy('orders.id', 'desc')
          ->paginate()
          ->withQueryString()
      ),
      'statuses' => [
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
        OrderStatusEnum::RESCHEDULE->value
      ]
    ]);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    return Inertia::render('Order/Create', [
      'clients' => Client::all(),
      'type_of_works' => TypeOfWork::all(),
      'types_of_housing' => TypeOfHousing::all(),
      'owners' => User::role(RoleEnum::OWNER->value)->get(),
      'installation_teams' => InstallationTeam::with(['user', 'typeHousing'])->get(),
      'supervisors' => User::role(RoleEnum::SUPERVISOR->value)->get(),
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
      'services' => [
        ServiceEnum::INSTALLATION->value,
        ServiceEnum::DELIVERY->value,
        ServiceEnum::PICKUP->value
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
      'product_costs' => ProductCost::all(),
      'status' => [
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::DELIVERY_CONFIRMED->value,
      ]
    ]);
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

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function edit(Order $order)
  {
    $status = [
      OrderStatusEnum::PLANNED->value,
      OrderStatusEnum::CONFIRMED->value,
      OrderStatusEnum::ON_HOLD->value,
      OrderStatusEnum::COMPLETE->value,
    ];

    if ($order->service === ServiceEnum::INSTALLATION->value) {
      $status = [
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        //OrderStatusEnum::EXECUTION->value,
        //OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::ON_HOLD->value,

      ];
      if ($order->status === OrderStatusEnum::CONFIRMED->value) {
        $status[] = OrderStatusEnum::RESCHEDULE->value;
      }
    }

    return Inertia::render('Order/Edit', [
      'order' => $order->load([
        'client',
        'typeOfWork',
        'typeOfHousing',
        'user',
        'attachments',
        'owners',
        'orderProducts.orderProductExtraWorks',
        'installationTeams.user',
      ]),
      'clients' => Client::all(),
      'type_of_works' => TypeOfWork::all(),
      'types_of_housing' => TypeOfHousing::all(),
      'owners' => User::role(RoleEnum::OWNER->value)->get(),
      'installation_teams' => InstallationTeam::with(['user', 'typeHousing'])->get(),
      'supervisors' => User::role(RoleEnum::SUPERVISOR->value)->get(),
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
      'status' => $status
    ]);
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

  public function dropAttachment($id)
  {

    // Buscar el attachment por ID
    $attachment = Attachment::find($id);

    // Verificar si el attachment existe
    if (!$attachment) {
      return redirect()
        ->back()
        ->with('error', 'Attachment not found');
    }

    // Obtener el usuario autenticado
    $user = auth()->user();

    if ($attachment->user_id === auth()->user()->id || $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::ACCOUNT_MANAGER->value])) {
      $attachment->delete();
      return redirect()
        ->back()
        ->with('success', 'Order deleted successfully.');
    } else {
      return redirect()
        ->back()
        ->with('error', 'You do not have permission to delete the file.');
    }
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

    $client = $estimate->client->replicate();
    $client->push(); // Guardar el nuevo cliente duplicado
    $newEstimate->client_id = $client->id; // Asignar el nuevo cliente a la orden duplicada
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
}
