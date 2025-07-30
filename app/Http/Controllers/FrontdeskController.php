<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderPipeline;
use App\Enum\ContactSourceEnum;
use App\Enum\FrontdeskStatusEnum;
use App\Enum\LostReasonfrontdeskEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Http\Requests\StoreFrontDeskOrderRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\User;
use Inertia\Inertia;

class FrontdeskController extends Controller
{
    public function index()
    {
      /* $data = [
        [
          'id' => 1,
          'title' => FrontdeskStatusEnum::NEW_CUSTOMER_REQUEST->value,
          'tasks' => [
            [
              'id' => 1,
              'title' => 'Task 1',
              'description' => 'Description for Task 1',
              'date' => 'Jun 18, 2023 10:00 AM',
              'names' => 'Salome Acosta',
              'precio'=> 1000,
            ],
            [
              'id' => 2,
              'title' => 'Task 2',
              'description' => 'Description for Task 2',
              'date' => 'Jun 18, 2023 10:00 AM',
              'names' => 'Salome Acosta',
              'precio'=> 1000,
            ]
          ]
        ],
        [
          'id' => 2,
          'title' => FrontdeskStatusEnum::NEW_REQUEST_FOLLOWUP->value,
          'tasks' => [
            [
              'id' => 3,
              'title' => 'Task 3',
              'description' => 'Description for Task 3',
              'date' => 'Jun 18, 2023 10:00 AM',
              'names' => 'Salome Acosta',
              'precio'=> 1000,
            ],
            [
              'id' => 4,
              'title' => 'Task 4',
              'description' => 'Description for Task 4',
              'date' => 'Jun 18, 2023 10:00 AM',
              'names' => 'Salome Acosta',
              'precio'=> 1000,
            ]
          ]
        ],
        [
          'id' => 3,
          'title' => FrontdeskStatusEnum::NEW_REQUEST_STANDBY->value,
          'tasks' => [
            [
              'id' => 3,
              'title' => 'Task 3',
              'description' => 'Description for Task 3',
              'date' => 'Jun 18, 2023 10:00 AM',
              'tags'=> ['designing', 'development'],
              'names' => 'Salome Acosta',
              'precio'=> 1000,
            ],
            [
              'id' => 4,
              'title' => 'Task 4',
              'description' => 'Description for Task 4',
              'date' => 'Jun 18, 2023 10:00 AM',
              'names' => 'Salome Acosta',
              'precio'=> 1000,
            ]
          ]
        ],
      ]; */
      // Definir los estados del Frontdesk (como strings usando el enum)
    $frontdeskStatuses = [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
        OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
        OrderStatusEnum::QUALIFIED->value,
    ];
    $lossReasonFrontdesk = [
        LostReasonfrontdeskEnum::NO_RESPONSE_FROM_CLIENT->value,
        LostReasonfrontdeskEnum::CLIENT_NOT_INTERESTED->value,
        LostReasonfrontdeskEnum::BUDGET_ISSUES->value,
        LostReasonfrontdeskEnum::OTHER_REASONS->value,
    ];

   

    // Obtener órdenes con esos estados y sus relaciones necesarias
    $orders = Order::with('client', 'user') // si tienes relación con el cliente
        ->whereIn('status', $frontdeskStatuses)
        ->get()
        ->groupBy('status');

    // Armar el arreglo que espera el componente React
    $data = collect($frontdeskStatuses)->map(function ($status) use ($orders) {
        $ordersByStatus = $orders[$status] ?? collect();

        return [
            'id' => $status, // puedes usar el valor del estado como id
            'title' => $status,
            'tasks' => $ordersByStatus->map(function ($order) {
                return [
                    'id' => $order->id,
                    'title' => $order->name ?? 'No Title',
                    'client_id' => $order->client_id ?? null,
                    //'description' => $order->notes ?? '',
                    'date' => optional($order->created_at)->format('M d, Y h:i A'),
                    //'names' => $order->user->name ?? 'No Name',
                    //'precio' => $order->price ?? 0,
                   'tags' => $order->tags ?? [], // si usas JSON
                ];
            })->values(),
        ];
    });

      return Inertia::render('Frontdesk/Index', [
        'data' => $data,
        'lossReasonFrontdesk' => $lossReasonFrontdesk,
      ]);
    }
    public function create()
  {
    return Inertia::render('Frontdesk/Create', [
      'clients' => Client::all(),
      'owners' => User::role(RoleEnum::OWNER->value)->get(),
      'status' => [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
        OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
      ],
      'sources' => [
       ContactSourceEnum::TIK_TOK->value,
        ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
        ContactSourceEnum::META->value,
        ContactSourceEnum::DESTINO_TOLK->value,
        ContactSourceEnum::RESOURCE_MAGAZINE->value,
        ContactSourceEnum::BANNER_PUBLICITARIO->value,
        ContactSourceEnum::PICHY_BOYS->value,
        ContactSourceEnum::GOOGLE_MY_BUSINESS->value,
      ],
    ]);
  }

   public function store(StoreFrontDeskOrderRequest $storeFrontDeskOrderRequest, CreateOrderPipeline $createOrderPipeline)
  {
    $createOrderPipeline->handle($storeFrontDeskOrderRequest);
    return redirect()->route('frontdesk.index')
      ->with('success', 'Request created successfully.');
  }

  public function updateStatus(Request $request, Order $order)
{
    $order->status = $request->input('status');

    $order->save();
      $order->orderStatus()->create([
        'status' => $request->input('status'),
        'user_id' => auth()->user()->id,
        'notes' => "{$request->input('status')} created by " . auth()->user()->name,
      ]);


    return response()->json(['success' => true, 'order' => $order]);
}

  public function updateStatusLost(Request $request, Order $order)
{     
    //dd($request->all());
    $order->status = $request->input('status');

    $order->save();
      $order->orderStatus()->create([
        'status' => $request->input('status'),
        'user_id' => auth()->user()->id,
        'notes' => "{$request->input('status')} created by " . auth()->user()->name,
      ]);


    return response()->json(['success' => true, 'order' => $order]);
}

}
