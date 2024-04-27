<?php

namespace App\Http\Controllers;

use App\Actions\ProduceOrder;
use App\Actions\UpdateOrderStatusNote;
use App\Http\Resources\OrderCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Enum\ProductSystemEnum;
use App\Enum\RoleEnum;
use App\Http\Requests\UpdateOrderStatusNoteRequest;
use App\Traits\Product;
use Illuminate\Contracts\Database\Eloquent\Builder;

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
              ->filter($request->only(['text', 'status']))
              ->orderBy('updated_at', 'desc')
              ->orderBy('id', 'desc')
              ->with(['products'])
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
      return redirect()
          ->back()
          ->with('success', 'Status updated successfully.');
    }

    public function noteUpdate(UpdateOrderStatusNoteRequest $updateOrderStatusNoteRequest, UpdateOrderStatusNote $statusOrderNote) 
    {
      $statusOrderNote->handle($updateOrderStatusNoteRequest);
      return redirect()
          ->back()
          ->with('success', 'Status Note updated successfully.');
    }

    public function status(Order $order) {
      $statuses = [];
      if ((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$ACCOUNTING)) && 
        (
          $order->status ==  OrderStatusEnum::$ACCOUNTING || 
          $order->status ==  OrderStatusEnum::$PRODUCTION_COMPLETED ||
          $order->status ==  OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED ||
          $order->status ==  OrderStatusEnum::$DELIVERED ||
          $order->status ==  OrderStatusEnum::$PICKED_UP
        )) {
          if ($order->status ==  OrderStatusEnum::$ACCOUNTING) {
            $statuses = [
              [
                'label' => OrderStatusEnum::$ESTIMATE,
                'value' => OrderStatusEnum::$ESTIMATE
              ],
              [
                'label' => OrderStatusEnum::$PRODUCTION,
                'value' => OrderStatusEnum::$PRODUCTION
              ],
            ];
          }
          else if ($order->status ==  OrderStatusEnum::$PRODUCTION_COMPLETED) {
            $statuses = [
              [
                'label' => OrderStatusEnum::$READY_FOR_DELIVERY,
                'value' => OrderStatusEnum::$READY_FOR_DELIVERY
              ],
              [
                'label' => OrderStatusEnum::$READY_FOR_PICKUP,
                'value' => OrderStatusEnum::$READY_FOR_PICKUP
              ],
            ];
          }
          else if ($order->status ==  OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED) {
            $statuses = [
              [
                'label' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY,
                'value' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY
              ],
              [
                'label' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP,
                'value' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP
              ],
            ];
          }
          else if ($order->status ==  OrderStatusEnum::$DELIVERED || $order->status ==  OrderStatusEnum::$PICKED_UP) {
            $statuses = [
              [
                'label' => OrderStatusEnum::$ORDER_COMPLETED,
                'value' => OrderStatusEnum::$ORDER_COMPLETED
              ],
            ];
          }
      }
      else if ((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER)) && 
        (
          $order->status ==  OrderStatusEnum::$PARTIAL_DELIVERED ||
          $order->status ==  OrderStatusEnum::$DELIVERED ||
          $order->status ==  OrderStatusEnum::$PARTIAL_PICKED_UP ||
          $order->status ==  OrderStatusEnum::$PICKED_UP ||
          $order->status ==  OrderStatusEnum::$READY_FOR_DELIVERY ||
          $order->status ==  OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY ||
          $order->status ==  OrderStatusEnum::$READY_FOR_PICKUP ||
          $order->status ==  OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP
        )) {

          if ($order->status == OrderStatusEnum::$DELIVERED || $order->status == OrderStatusEnum::$PICKED_UP) {
            $statuses = [
              [
                'label' => OrderStatusEnum::$ORDER_COMPLETED,
                'value' => OrderStatusEnum::$ORDER_COMPLETED
              ],
            ];
          } else if ($order->status == OrderStatusEnum::$READY_FOR_DELIVERY || $order->status == OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY || $order->status == OrderStatusEnum::$READY_FOR_PICKUP || $order->status == OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP) {
            $statuses = [
              [
                'label' => OrderStatusEnum::$DELIVERED,
                'value' => OrderStatusEnum::$DELIVERED
              ],
              [
                'label' => OrderStatusEnum::$PARTIAL_DELIVERED,
                'value' => OrderStatusEnum::$PARTIAL_DELIVERED
              ],
              [
                'label' => OrderStatusEnum::$PICKED_UP,
                'value' => OrderStatusEnum::$PICKED_UP
              ],
              [
                'label' => OrderStatusEnum::$PARTIAL_PICKED_UP,
                'value' => OrderStatusEnum::$PARTIAL_PICKED_UP
              ],
            ];
          }
      }
      else if ((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$PRODUCTION)) && 
        (
          $order->status ==  OrderStatusEnum::$PRODUCTION ||
          $order->status ==  OrderStatusEnum::$PRODUCTION_IN_PROGRESS ||
          $order->status == OrderStatusEnum::$SCHEDULED_PRODUCTION ||
          $order->status == OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED ||
          $order->status == OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY ||
          $order->status == OrderStatusEnum::$PARTIAL_DELIVERED ||
          $order->status == OrderStatusEnum::$PARTIAL_PICKED_UP
        )) {
        if ($order->status ==  OrderStatusEnum::$PRODUCTION) {
          $statuses = [
            [
              'label' => OrderStatusEnum::$SCHEDULED_PRODUCTION,
              'value' => OrderStatusEnum::$SCHEDULED_PRODUCTION
            ],
          ];
        } else if ($order->status ==  OrderStatusEnum::$SCHEDULED_PRODUCTION) {
          $statuses = [
            [
              'label' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS,
              'value' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS
            ]
          ];
        }
        else if ($order->status == OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY) {
          $statuses = [
            [
              'label' => OrderStatusEnum::$PRODUCTION_COMPLETED,
              'value' => OrderStatusEnum::$PRODUCTION_COMPLETED
            ]
          ];
        }
        else {
          $statuses = [
            [
              'label' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED,
              'value' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED
            ],
            [
              'label' => OrderStatusEnum::$PRODUCTION_COMPLETED,
              'value' => OrderStatusEnum::$PRODUCTION_COMPLETED
            ]
          ];
        }
      }
      else if ((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$SHIPPING)) && 
      ( 
        $order->status ==  OrderStatusEnum::$READY_FOR_DELIVERY ||
        $order->status ==  OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY ||
        $order->status ==  OrderStatusEnum::$READY_FOR_PICKUP ||
        $order->status ==  OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP
      )) {

        if ($order->status == OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY || $order->status == OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP) {
          $statuses = [
            [
              'label' => OrderStatusEnum::$PARTIAL_DELIVERED,
              'value' => OrderStatusEnum::$PARTIAL_DELIVERED
            ],
            [
              'label' => OrderStatusEnum::$PARTIAL_PICKED_UP,
              'value' => OrderStatusEnum::$PARTIAL_PICKED_UP
            ],
          ];
        } else if ($order->status == OrderStatusEnum::$READY_FOR_DELIVERY || $order->status == OrderStatusEnum::$READY_FOR_PICKUP) {
          $statuses = [
            [
              'label' => OrderStatusEnum::$DELIVERED,
              'value' => OrderStatusEnum::$DELIVERED
            ],
            [
              'label' => OrderStatusEnum::$PICKED_UP,
              'value' => OrderStatusEnum::$PICKED_UP
            ],
          ];
        }
      }
      else if ((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$PLANT_MANAGER)) && 
      ( 
        $order->status ==  OrderStatusEnum::$PRODUCTION_IN_PROGRESS ||
        $order->status ==  OrderStatusEnum::$READY_FOR_DELIVERY ||
        $order->status ==  OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY ||
        $order->status ==  OrderStatusEnum::$READY_FOR_PICKUP ||
        $order->status ==  OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP ||
        $order->status ==  OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED ||
        $order->status ==  OrderStatusEnum::$PRODUCTION_COMPLETED
      )) {
          $statuses = [
            [
              'label' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED,
              'value' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED
            ],
            [
              'label' => OrderStatusEnum::$PRODUCTION_COMPLETED,
              'value' => OrderStatusEnum::$PRODUCTION_COMPLETED
            ]
          ];
        
      }
      else if (auth()->user()->hasRole(RoleEnum::$SUB_DEALER) && $order->status ==  OrderStatusEnum::$SUB_DEALER_ESTIMATE) {
          $statuses = [
            [
              'label' => 'Estimate to Order',
              'value' => OrderStatusEnum::$ESTIMATE
            ]
          ];
      }
      else if (auth()->user()->hasRole(RoleEnum::$DEALER) && 
      (
        $order->status == OrderStatusEnum::$ESTIMATE ||
        $order->status == OrderStatusEnum::$SUB_DEALER_ESTIMATE 
      )) {
          if ($order->status == OrderStatusEnum::$ESTIMATE) {
            $statuses = [
              [
                'label' => 'Return to Sub Dealer',
                'value' => OrderStatusEnum::$SUB_DEALER_ESTIMATE
              ],
            ];
          }
          else if ($order->status == OrderStatusEnum::$SUB_DEALER_ESTIMATE) {
            $statuses = [
              [
                'label' => 'Estimate to Order',
                'value' => OrderStatusEnum::$ESTIMATE
              ]
            ];
          }
      }

      return response()->json($statuses);
    }

    public function statusFilter() {
      $statuses = [];
      if (
          auth()->user()->hasRole(RoleEnum::$ADMIN) || 
          auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER) || 
          auth()->user()->hasRole(RoleEnum::$ACCOUNTING) ||
          auth()->user()->hasRole(RoleEnum::$DEALER)
          ) {
          $statuses = [
            [
              'label' => OrderStatusEnum::$ACCOUNTING,
              'value' => OrderStatusEnum::$ACCOUNTING
            ],
            [
              'label' => OrderStatusEnum::$PRODUCTION,
              'value' => OrderStatusEnum::$PRODUCTION
            ],
            [
              'label' => OrderStatusEnum::$SCHEDULED_PRODUCTION,
              'value' => OrderStatusEnum::$SCHEDULED_PRODUCTION
            ],
            [
              'label' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS,
              'value' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS
            ],
            [
              'label' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED,
              'value' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED
            ],
            [
              'label' => OrderStatusEnum::$PRODUCTION_COMPLETED,
              'value' => OrderStatusEnum::$PRODUCTION_COMPLETED
            ],
            [
              'label' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP,
              'value' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP
            ],
            [
              'label' => OrderStatusEnum::$READY_FOR_PICKUP,
              'value' => OrderStatusEnum::$READY_FOR_PICKUP
            ],
            [
              'label' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY,
              'value' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY
            ],
            [
              'label' => OrderStatusEnum::$READY_FOR_DELIVERY,
              'value' => OrderStatusEnum::$READY_FOR_DELIVERY
            ],
            [
              'label' => OrderStatusEnum::$PARTIAL_PICKED_UP,
              'value' => OrderStatusEnum::$PARTIAL_PICKED_UP
            ],
            [
              'label' => OrderStatusEnum::$PICKED_UP,
              'value' => OrderStatusEnum::$PICKED_UP
            ],
            [
              'label' => OrderStatusEnum::$PARTIAL_DELIVERED,
              'value' => OrderStatusEnum::$PARTIAL_DELIVERED
            ],
            [
              'label' => OrderStatusEnum::$DELIVERED,
              'value' => OrderStatusEnum::$DELIVERED
            ],
            [
              'label' => OrderStatusEnum::$ORDER_COMPLETED,
              'value' => OrderStatusEnum::$ORDER_COMPLETED
            ]
          ];
      }
      else if (auth()->user()->hasRole(RoleEnum::$PRODUCTION)) {
        $statuses = [
          [
            'label' => OrderStatusEnum::$PRODUCTION,
            'value' => OrderStatusEnum::$PRODUCTION
          ],
          [
            'label' => OrderStatusEnum::$SCHEDULED_PRODUCTION,
            'value' => OrderStatusEnum::$SCHEDULED_PRODUCTION
          ],
          [
            'label' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS,
            'value' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS
          ],
          [
            'label' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED,
            'value' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED
          ],
          [
            'label' => OrderStatusEnum::$PRODUCTION_COMPLETED,
            'value' => OrderStatusEnum::$PRODUCTION_COMPLETED
          ],
          [
            'label' => OrderStatusEnum::$PARTIAL_PICKED_UP,
            'value' => OrderStatusEnum::$PARTIAL_PICKED_UP
          ],
          [
            'label' => OrderStatusEnum::$PARTIAL_DELIVERED,
            'value' => OrderStatusEnum::$PARTIAL_DELIVERED
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_DELIVERY,
            'value' => OrderStatusEnum::$READY_FOR_DELIVERY
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PICKUP,
            'value' => OrderStatusEnum::$READY_FOR_PICKUP
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY,
            'value' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP,
            'value' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP
          ],
          [
            'label' => OrderStatusEnum::$ORDER_COMPLETED,
            'value' => OrderStatusEnum::$ORDER_COMPLETED
          ]
        ];
      }
      else if (auth()->user()->hasRole(RoleEnum::$PLANT_MANAGER)) {
        $statuses = [
          [
            'label' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS,
            'value' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS
          ],
          [
            'label' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED,
            'value' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED
          ],
          [
            'label' => OrderStatusEnum::$PRODUCTION_COMPLETED,
            'value' => OrderStatusEnum::$PRODUCTION_COMPLETED
          ],
          [
            'label' => OrderStatusEnum::$PARTIAL_PICKED_UP,
            'value' => OrderStatusEnum::$PARTIAL_PICKED_UP
          ],
          [
            'label' => OrderStatusEnum::$PARTIAL_DELIVERED,
            'value' => OrderStatusEnum::$PARTIAL_DELIVERED
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_DELIVERY,
            'value' => OrderStatusEnum::$READY_FOR_DELIVERY
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PICKUP,
            'value' => OrderStatusEnum::$READY_FOR_PICKUP
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY,
            'value' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP,
            'value' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP
          ],
          [
            'label' => OrderStatusEnum::$ORDER_COMPLETED,
            'value' => OrderStatusEnum::$ORDER_COMPLETED
          ]
        ];
      }
      else if (auth()->user()->hasRole(RoleEnum::$SHIPPING)) {
        $statuses = [
          [
            'label' => OrderStatusEnum::$READY_FOR_DELIVERY,
            'value' => OrderStatusEnum::$READY_FOR_DELIVERY
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY,
            'value' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PICKUP,
            'value' => OrderStatusEnum::$READY_FOR_PICKUP
          ],
          [
            'label' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP,
            'value' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP
          ],
          [
            'label' => OrderStatusEnum::$DELIVERED,
            'value' => OrderStatusEnum::$DELIVERED
          ],
          [
            'label' => OrderStatusEnum::$PICKED_UP,
            'value' => OrderStatusEnum::$PICKED_UP
          ],
          [
            'label' => OrderStatusEnum::$PARTIAL_DELIVERED,
            'value' => OrderStatusEnum::$PARTIAL_DELIVERED
          ],
          [
            'label' => OrderStatusEnum::$PARTIAL_PICKED_UP,
            'value' => OrderStatusEnum::$PARTIAL_PICKED_UP
          ],
        ];
      }

      return response()->json($statuses);
    }

    public function workOrder(Order $order) {
      $order->load(['products' => function (Builder $builder) {
        $builder->orderBy('product_sort', 'asc');
      }, 'client']);
      
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
          'order' => Order::with(['client', 'products' => function (Builder $builder) {
            $builder->orderBy('product_sort', 'asc');
          }, 'payments'])->findOrFail($id),
          'statuses' => [
            OrderStatusEnum::$ESTIMATE,
            OrderStatusEnum::$PRODUCTION,
            OrderStatusEnum::$READY_FOR_DELIVERY
          ]
        ]);
    }

    public function history(Order $order) {
      $order->load(['orderStatus' => function(Builder $query) {
        $query->orderBy('id', 'desc');
      }, 'orderStatus.user']);
      return response()->json($order->orderStatus);
    }
}
