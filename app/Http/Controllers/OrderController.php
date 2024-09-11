<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\Actions\ProduceOrder;
use App\Actions\UpdateOrder;
use App\Actions\UpdateOrderStatusNote;
use App\Enum\FrameColorEnum;
use App\Enum\MethodOfPayment;
use App\Http\Resources\OrderCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Enum\ProductSystemEnum;
use App\Enum\RoleEnum;
use App\Enum\Service;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusNoteRequest;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\DurationOfWork;
use App\Models\ExtraWork;
use App\Models\InstallationTeam;
use App\Models\ProductCategory;
use App\Models\ProductConfig;
use App\Models\ProductCost;
use App\Models\TravelCost;
use App\Models\TypeOfHousing;
use App\Models\TypeOfProduct;
use App\Models\TypeOfWork;
use App\Models\User;
use App\Traits\OrderDates;
use Doctrine\DBAL\Types\Type;
use Illuminate\Contracts\Database\Eloquent\Builder;

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
            OrderStatusEnum::FINAL_INSPECTION->value,
            OrderStatusEnum::FINAL_COLLECT->value
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
          ],
          'services' => [
            Service::INSTALLATION->value,
            Service::DELIVERY->value,
            Service::PICKUP->value
          ],
          'frame_colors' => [
            FrameColorEnum::WHITE->value,
            FrameColorEnum::BLACK->value,
            FrameColorEnum::BRONZE->value,
            FrameColorEnum::CLEAR_ANODIZED->value
          ],
          'travel_costs' => TravelCost::all(),
          'duration_of_works' => DurationOfWork::all(),
          'products_config' => ProductConfig::all(),
          'type_of_products' => TypeOfProduct::with(['extraWorks'])->get(),
          'product_category' => ProductCategory::all(),
          'extra_works' => ExtraWork::all(),
          'product_costs' => ProductCost::all(),
        ]);
    }

    public function getDeliveryAndInstallationDate($payment_factory_date, $type_of_housing, $county_id, $service) {
      $estimate_eta_date = $this->estimateETADate($payment_factory_date);
      $estimate_delivery_date = $this->getEstimateDeliveryDate($payment_factory_date, $service, $county_id, $type_of_housing);
      $estimate_installation_date = $this->getEstimateInstallationDate($estimate_delivery_date, $service);

      return response()->json([
        'estimate_eta_date' => $estimate_eta_date,
        'estimate_delivery_date' => $estimate_delivery_date,
        'estimate_installation_date' => $estimate_installation_date
      ]);
    }

    public function getDeliveryAndPickupDate($payment_factory_date) {
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
          ],
          'frame_colors' => [
            FrameColorEnum::WHITE->value,
            FrameColorEnum::BLACK->value,
            FrameColorEnum::BRONZE->value,
            FrameColorEnum::CLEAR_ANODIZED->value
          ],
          'services' => [
            Service::INSTALLATION->value,
            Service::DELIVERY->value,
            Service::PICKUP->value
          ],
          'travel_costs' => TravelCost::all(),
          'duration_of_works' => DurationOfWork::all(),
          'products_config' => ProductConfig::all(),
          'type_of_products' => TypeOfProduct::with(['extraWorks'])->get(),
          'product_category' => ProductCategory::all(),
          'product_costs' => ProductCost::all(),
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

    public function dropAttachment($id) {
      $attachment = Attachment::find($id);
      $attachment->delete();
    }

}
