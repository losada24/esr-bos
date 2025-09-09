<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderPipeline;
use App\Actions\CreateQualifiedOrder;
use App\Enum\ContactSourceEnum;
use App\Enum\FrontdeskStatusEnum;
use App\Enum\LostReasonfrontdeskEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\RoleEnum;
use App\Http\Requests\StoreFrontDeskOrderRequest;
use App\Http\Requests\StoreQualifiedOrderRequest;
use App\Models\Client;
use App\Models\CompanyContact;
use Illuminate\Http\Request;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Source;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FrontdeskController extends Controller
{
    public function index()
    {
      
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

   

   $orders = Order::with(['client','user','orderStatus','tags:id,name,color,taggable_id,taggable_type'])
    ->whereIn('status', $frontdeskStatuses)
    ->get()
    ->groupBy('status');

$qualifiedOrderIds = OrderStatus::where('status', OrderStatusEnum::QUALIFIED->value)
    ->whereHas('order')
    ->pluck('order_id')
    ->unique()
    ->values();

$qualifiedOrders = Order::with(['client','user','orderStatus','tags:id,name,color,taggable_id,taggable_type'])
    ->whereIn('id', $qualifiedOrderIds)
    ->get();

$data = collect($frontdeskStatuses)->map(function ($status) use ($orders, $qualifiedOrders) {
    $ordersByStatus = $status === OrderStatusEnum::QUALIFIED->value
        ? $qualifiedOrders
        : ($orders[$status] ?? collect());

    return [
        'id' => $status,
        'title' => $status,
        'tasks' => $ordersByStatus->map(function ($order) {
            return [
                'id'          => $order->id,
                'title'       => $order->name ?? 'No Title',
                'client_id'   => $order->client_id ?? null,
                'date_edited' => optional($order->updated_at)->format('M d, Y h:i A'),
                'date'        => optional($order->created_at)->format('M d, Y h:i A'),
                'tags'        => ($order->tags ?? collect())->map(fn($t) => [
                    'name'  => $t->name,
                    'color' => $t->color,
                ])->values(),
            ];
        })->values(),
    ];
});
      return Inertia::render('Frontdesk/Index', [
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

   public function createQualified()
  {
    return Inertia::render('Frontdesk/CreateQualified', [
      'clients' => Client::all(),
      'owners' => User::role(RoleEnum::OWNER->value)->get(),
      'status' => [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
        OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
        OrderStatusEnum::QUALIFIED->value,
      ],
      'sources' => Source::all(),
      'sourcesClients' => [
            ContactSourceEnum::TIK_TOK->value,
            ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
            ContactSourceEnum::META->value,
            ContactSourceEnum::DESTINO_TOLK->value,
            ContactSourceEnum::RESOURCE_MAGAZINE->value,
            ContactSourceEnum::BANNER_PUBLICITARIO->value,
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::GOOGLE_MY_BUSINESS->value,
            ContactSourceEnum::PICHY_BOYS->value,
          ],
      //dd(Source::all()),
      'order_types' => [
        OrderTypeEnum::RESIDENTIAL->value,
        OrderTypeEnum::COMMERCIAL->value,
        OrderTypeEnum::SUPPLY->value,
      ],
      'companies' => CompanyContact::all(),
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
        $status = $request['status'];
        if ($request['order_type'] === OrderTypeEnum::RESIDENTIAL->value || $request['order_type'] === OrderTypeEnum::SUPPLY->value) {
          $status = OrderStatusEnum::PENDING_ASSIGNMENT->value;
        } 
        if ($request['order_type'] === OrderTypeEnum::COMMERCIAL->value) {
          $status = OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value;
        }
        
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
        'status' => $status,
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

          $order->orderStatus()->create([
            'status' => $status,
            'user_id' => auth()->user()->id,
            'notes' => "{$status} created by " . auth()->user()->name,
          ]);



        return response()->json(['success' => true, 'order' => $order]);
    }

    public function storeQualifiedOrder(StoreQualifiedOrderRequest $storeQualifiedOrderRequest, CreateQualifiedOrder $createQualifiedOrder)
  {
    $createQualifiedOrder->handle($storeQualifiedOrderRequest);
    return redirect()->route('frontdesk.index')
      ->with('success', 'Contact created successfully.');
  }
  
    public function orderView($id)
  { // Obtener las órdenes por supervisor

    $order = Order::find($id);
    $order->load('tags:id,name,color,taggable_id,taggable_type', 'client', 'user',  'attachments', 'orderStatus.user');
    $orderStatuses = OrderStatus::where('order_id', $id)
      ->with(['order', 'user'])
      ->get();

        $usedTags = Tag::select('name', 'color', DB::raw('COUNT(*) AS count'))
            ->where('type', 'order')     // o ->where('taggable_type', Order::class)
            ->groupBy('name', 'color')
            ->orderByDesc('count')
            ->limit(200)
            ->get()
            ->map(fn ($t) => [
                'name'  => $t->name,
                'color' => $t->color ?? 'gray',
                'count' => (int) $t->count,
            ]);

            //dd($usedTags);


    // Obtener los parámetros de filtro de la solicitud (request)
    return Inertia::render('Frontdesk/OrderView', [
      //'orderStatuses' => $orderStatuses,
      'order' => $order,
       'tags' => $order->tags->map(fn($t) => [
                'name'  => $t->name,
                'color' => $t->color,
            ]),
      'usedTags' => $usedTags,
      /*'orderStatuses' => $orderStatuses->map(function ($status) {
        return [
          ...$status->toArray(),
          'created_at_formatted' => Carbon::parse($status->created_at)
            ->setTimezone('America/New_York')
            ->format('Y-m-d'),
        ];
      }),*/


    ]);
  }

  public function tagsUpdate(Request $request, Order $order)
    {
        $validated = $request->validate([
            'tags'         => ['array'],
            'tags.*.name'  => ['required', 'string', 'max:28'],
            'tags.*.color' => ['nullable', 'string', 'in:none,red,orange,amber,yellow,lime,green,teal,sky,blue,indigo,violet,purple,pink,gray'],
        ]);

        // Normaliza y elimina duplicados por nombre (case-insensitive)
        $uniqueTags = collect($validated['tags'] ?? [])
            ->map(fn($t) => [
                'name'  => trim($t['name']),
                'color' => $t['color'] ?? 'gray',
            ])
            ->filter(fn($t) => $t['name'] !== '')
            ->unique(fn($t) => mb_strtolower($t['name']))
            ->values()
            ->all();

        DB::transaction(function () use ($order, $uniqueTags) {
            $this->replaceTags($order, $uniqueTags, 'order');
        });

        return back()->with('success', 'Tags actualizados.');
    }

      protected function replaceTags($model, array $tags, string $type): void
      {
          // ❌ Antes: ->where('taggable_type', $type)  // estaba mal
          // ✅ Ahora: borra los tags de ESTE modelo (morph) y solo del type lógico dado
          $model->tags()
              ->when($type, fn($q) => $q->where('type', $type))
              ->delete();

          if (!empty($tags)) {
              $rows = array_map(fn($t) => [
                  'name'    => $t['name'],
                  'color'   => $t['color'] ?? 'gray',
                  'type'    => $type,          // tu tipo lógico
                  'user_id' => auth()->id(),
              ], $tags);

              $model->tags()->createMany($rows);
          }
      }

}