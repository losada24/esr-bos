<?php

namespace App\Http\Controllers;

use App\Actions\ProduceOrder;
use App\Http\Resources\OrderCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Http\Requests\UpdateOrderStatusRequest;

class OrderController extends Controller
{
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
            OrderStatusEnum::$ORDER_COMPLETED
          ]
        ]);
    }

    public function statusUpdate(UpdateOrderStatusRequest $updateOrderStatusRequest, ProduceOrder $produceOrder) 
    {
      $produceOrder->handle($updateOrderStatusRequest);
      return redirect()->route('order.index')
          ->with('success', 'Order updated successfully.');
    }

    public function completeProduction(Request $request) 
    {
      $order = Order::find($request->id);
      if( !$order )
      {
          throw new \Exception('Not not updated');
      }

      $orderData = [
        'status' => OrderStatusEnum::$ORDER_COMPLETED
      ];

      $order->update($orderData);
      return redirect()->route('order.index')
          ->with('success', 'Order completed successfully.');
    }

    public function workOrder(Order $order) {
      $order->load(['products', 'client']);
      return Inertia::render('Order/WorkOrder', [
        'order' => $order
      ]);
    }

    public function show($id)
    {
        return Inertia::render('Order/Show', [
          'order' => Order::with(['client', 'products'])->findOrFail($id)
        ]);
    }
}
