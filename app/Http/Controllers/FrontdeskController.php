<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderPipeline;
use App\Actions\CreateQualifiedOrder;
use App\Enum\ContactSourceEnum;
use App\Enum\FrameColorEnum;
use App\Enum\FrontdeskStatusEnum;
use App\Enum\GlassCoatingEnum;
use App\Enum\GlassColorEnum;
use App\Enum\GlassTypeEnum;
use App\Enum\LanguageEnum;
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
use App\Models\SaleForm;
use App\Models\Source;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

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
                'schedule_appointment' => $order->schedule_appointment ? Carbon::parse($order->schedule_appointment)->format('M d, Y h:i A') : null,
                'phone'       => $order->client->phone ?? null,
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
      'frame_colors' => [
        FrameColorEnum::BLACK->value,
        FrameColorEnum::WHITE->value,
        FrameColorEnum::BRONZE->value,
        FrameColorEnum::CLEAR_ANODIZED->value,
        FrameColorEnum::OTHERS->value,
      ],
      'glass_colors' => [
        GlassColorEnum::BRONZE->value,
        GlassColorEnum::CLEAR->value,
        GlassColorEnum::GRAY->value,
        GlassColorEnum::GREEN->value,
        GlassColorEnum::OTHERS->value,
      ],
      //dd(Source::all()),
      'order_types' => [
        OrderTypeEnum::RESIDENTIAL->value,
        OrderTypeEnum::COMMERCIAL->value,
      ],
      'glass_coatings' => [
        GlassCoatingEnum::LOWE70->value,
        GlassCoatingEnum::LOWE60->value,
      ],
      'glass_types' => [
        GlassTypeEnum::LAMINATED->value,
        GlassTypeEnum::INSULATED->value,
        GlassTypeEnum::INSULATED_LAMINATED->value,
      ],
      'languages' => array_map(
        static fn (LanguageEnum $language) => $language->value,
        LanguageEnum::cases()
      ),
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
        'is_supply' => $request['is_supply'] ?? false,

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
    $order->load('tags:id,name,color,taggable_id,taggable_type', 'client.companyContact', 'user', 'owners', 'saleForm', 'attachments.user', 'orderStatus.user');

    if ($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value && !$order->saleForm) {
      $order->saleForm()->create($this->buildSaleFormPayload($order, null));
      $order->load('saleForm');
    }
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

  public function saleFormPdf(Request $request, Order $order)
  {
    $order->load(['client.companyContact', 'owners', 'saleForm']);

    if (!$order->saleForm) {
      abort(404, 'Sale form not found for this order.');
    }

    $pdf = Pdf::loadView('pdf.sale-form', [
      'order' => $order,
    ]);

    $filename = 'sale-form-' . ($order->order_number ?? $order->id) . '.pdf';

    if ($request->boolean('download')) {
      return $pdf->download($filename);
    }

    return $pdf->stream($filename, ['Attachment' => false]);
  }

  public function tagsUpdate(Request $request, Order $order)
  {
      $validated = $request->validate([
          'tags'         => ['array'],
          'tags.*.name'  => ['required', 'string', 'max:28'],
          'tags.*.color' => ['nullable', 'string', 'in:none,red,orange,amber,yellow,lime,green,teal,sky,blue,indigo,violet,purple,pink,gray'],
      ]);

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
      $model->tags()
          ->when($type, fn($q) => $q->where('type', $type))
          ->delete();

      if (!empty($tags)) {
          $rows = array_map(fn($t) => [
              'name'    => $t['name'],
              'color'   => $t['color'] ?? 'gray',
              'type'    => $type,
              'user_id' => auth()->id(),
          ], $tags);

          $model->tags()->createMany($rows);
      }
  }

  protected function buildSaleFormPayload(Order $order, ?SaleForm $existing = null): array
  {
      return [
          'sale' => $existing?->sale ?? false,
          'installation' => $existing?->installation ?? false,
          'permit' => $existing?->permit ?? false,
          'replacement' => $existing?->replacement ?? false,
          'new_construction' => $existing?->new_construction ?? false,
          'financing' => $existing?->financing ?? false,
          'screen' => $existing?->screen ?? false,
          'design' => $existing?->design ?? false,
          'mountin' => $existing?->mountin ?? false,
          'bar' => $existing?->bar ?? false,
          'shutter_hole' => $existing?->shutter_hole ?? false,
          'floor_cutting' => $existing?->floor_cutting ?? false,
          'interior_finish' => $existing?->interior_finish ?? false,
          'floor' => $existing?->floor ?? '',
          'frame_color' => $existing?->frame_color ?? ($order->frame_color ?? ''),
          'glass_color' => $existing?->glass_color ?? ($order->glass_color ?? ''),
          'glass_type' => $existing?->glass_type ?? ($order->glass_type ?? ''),
          'glass_coating' => $existing?->glass_coating ?? ($order->glass_coating ?? ''),
          'door_quantity' => $existing?->door_quantity ?? 0,
          'window_quantity' => $existing?->window_quantity ?? 0,
      ];
  }




}

  
