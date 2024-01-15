<?php

namespace App\Http\Controllers;

use App\Actions\ProduceOrder;
use App\Http\Resources\OrderCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Enum\ProductSystemEnum;
use App\Enum\RoleEnum;
use App\Models\OrderStatus;
use App\Products\FixedWindowsProduct;
use App\Products\HorizontalRollerProduct;
use App\Products\SingleHuntProduct;
use App\Traits\Product;


class OrderController extends Controller
{

    use Product;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('Order/Index', [
          'orders' => new OrderCollection(
            Order::orders()
              ->filter($request->only(['text']))
              ->orderBy('id', 'desc')
              ->paginate()
              ->withQueryString()
          ),
          'statuses' => [
            OrderStatusEnum::$ESTIMATE,
            OrderStatusEnum::$PRODUCTION,
            OrderStatusEnum::$READY_FOR_DELIVERY
          ]
        ]);
    }

    public function statusUpdate(UpdateOrderStatusRequest $updateOrderStatusRequest, ProduceOrder $produceOrder) 
    {
      $produceOrder->handle($updateOrderStatusRequest);
      return redirect()->route('order.index')
          ->with('success', 'Order updated successfully.');
    }

    /* public function completeProduction(Request $request) 
    {
      $order = Order::find($request->id);
      if( !$order )
      {
          throw new \Exception('Not not updated');
      }

      // TODO: Insert status history
      $orderData = [
        'status' => OrderStatusEnum::$PRODUCTION_COMPLETED
      ];

      $order->update($orderData);
      return redirect()->route('order.index')
          ->with('success', 'Order completed successfully.');
    } */

    public function status(Order $order) {
      $statuses = [];
      if (((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$ACCOUNTING)) && 
        ($order->status ==  OrderStatusEnum::$ACCOUNTING || $order->status ==  OrderStatusEnum::$PRODUCTION_COMPLETED))) {
          if ($order->status ==  OrderStatusEnum::$ACCOUNTING) {
            $statuses = [
              OrderStatusEnum::$ESTIMATE,
              OrderStatusEnum::$PRODUCTION
            ];
          }
          else if ($order->status ==  OrderStatusEnum::$PRODUCTION_COMPLETED) {
            $statuses = [
              OrderStatusEnum::$READY_FOR_DELIVERY
            ];
          }
      }
      else if (((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$PRODUCTION)) && 
        $order->status ==  OrderStatusEnum::$PRODUCTION)) {
        $statuses = [
          OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED,
          OrderStatusEnum::$PRODUCTION_COMPLETED,
          OrderStatusEnum::$SCHEDULED_PRODUCTION,
          OrderStatusEnum::$PRODUCTION_IN_PROGRESS
        ];
      }
      else if (((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$SHIPPING)) && 
        $order->status ==  OrderStatusEnum::$READY_FOR_DELIVERY)) {
        $statuses = [
          OrderStatusEnum::$DELIVERED,
          OrderStatusEnum::$PARTIAL_DELIVERED
        ];
      }

      return response()->json($statuses);
    }

    public function workOrder(Order $order) {
      $order->load(['products', 'client']);
      
      $materialConsumption = $this->getMaterialConsumption($order);
      $cuttingList = $this->getCuttingList($order);

      $orderData = [
        'id' => $order->id,
        'name' => $order->name,
        'client' => $order->client,
        'created_at' => $order->created_at,
        'project_name' => $order->project_name,
        'products' => $cuttingList,
        'materialConsumption' => $materialConsumption
      ];

      return Inertia::render('Order/WorkOrder', [
        'order' => $orderData
      ]);
    }

    public function show($id)
    {   // TODO: Send roles by auth user to always use same modal form to update status
        return Inertia::render('Order/Show', [
          'order' => Order::with(['client', 'products', 'payments'])->findOrFail($id),
          'statuses' => [
            OrderStatusEnum::$ESTIMATE,
            OrderStatusEnum::$PRODUCTION,
            OrderStatusEnum::$READY_FOR_DELIVERY
          ]
        ]);
    }
}
