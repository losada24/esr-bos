<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderPipeline;
use App\Enum\ContactSourceEnum;
use App\Enum\FrontdeskStatusEnum;
use App\Enum\LostReasonfrontdeskEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\RoleEnum;
use App\Http\Requests\StoreFrontDeskOrderRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\User;
use Inertia\Inertia;

class SalesController extends Controller
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
    $salesStatuses = [
        OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value,
        OrderStatusEnum::PENDING_ASSIGNMENT->value,
        OrderStatusEnum::REQUEST_RE_SCHEDULE->value,
        OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
        OrderStatusEnum::FOLLOW_UP->value,
        OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
        OrderStatusEnum::STAND_BY->value,
        OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value,
        OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
    ];
    $lossReasonFrontdesk = [
        LostReasonfrontdeskEnum::NO_RESPONSE_FROM_CLIENT->value,
        LostReasonfrontdeskEnum::CLIENT_NOT_INTERESTED->value,
        LostReasonfrontdeskEnum::BUDGET_ISSUES->value,
        LostReasonfrontdeskEnum::OTHER_REASONS->value,
    ];
    $sources = [
        ContactSourceEnum::TIK_TOK->value,
        ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
        ContactSourceEnum::META->value,
        ContactSourceEnum::DESTINO_TOLK->value,
        ContactSourceEnum::RESOURCE_MAGAZINE->value,
        ContactSourceEnum::BANNER_PUBLICITARIO->value,
        ContactSourceEnum::PICHY_BOYS->value,
        ContactSourceEnum::GOOGLE_MY_BUSINESS->value,
    ];

    $order_types = [
      OrderTypeEnum::RESIDENTIAL->value,
      OrderTypeEnum::COMMERCIAL->value,
      OrderTypeEnum::SUPPLY->value,
    ];

   

    // Obtener órdenes con esos estados y sus relaciones necesarias
    $orders = Order::with('client', 'user') // si tienes relación con el cliente
        ->whereIn('status', $salesStatuses)
        ->get()
        ->groupBy('status');

    // Armar el arreglo que espera el componente React
    $data = collect($salesStatuses)->map(function ($status) use ($orders) {
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

      return Inertia::render('Sales/Index', [
        'data' => $data,
        'lossReasonFrontdesk' => $lossReasonFrontdesk,
        'sources' => $sources,
        'order_types' => $order_types,
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
        OrderStatusEnum::QUALIFIED->value,
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

public function showQuantifiedModal(Order $order)
{
    $order->load('client'); // Relación con Client

    return response()->json($order);
}
    
    public function updateStatusQuantified(Request $request, Order $order)
    {     
        //dd($request->all());
        $order->update([
        'name' => $request['name'],
        'order_type' => $request['order_type'],
        'job_address' => $request['job_address'],
        'city' => $request['city'],
        'job_state' => $request['job_state'],
        'job_zip' => $request['job_zip'],
        'bid_due_date' => $request['bid_due_date'],
        'project_amount' => $request['project_amount'],
        'description' => $request['description'],
        'status' => $request['status'],
    ]);

      if ($order->client) {
        $order->client->update([
            'name' => $request['client_name'],
            'source' =>$request['source'],
            'email' => $request['email'],
            'secondary_email' => $request['secondary_email'],
            'phone' => $request['phone'],
            'other_phone' => $request['other_phone'],
            'vip_clients' => $request['vip_clients'] ?? false,
            'vip_notes' => $request['vip_notes'],
            'is_contact' => true,
            'user_id' => auth()->user()->id,
        ]);
    }
          $order->orderStatus()->create([
            'status' => $request['status'],
            'user_id' => auth()->user()->id,
            'notes' => "{$request['status']} created by " . auth()->user()->name,
          ]);



        return response()->json(['success' => true, 'order' => $order]);
    }

}