<?php

namespace App\Http\Controllers;

use App\Actions\ProduceOrder;
use App\Actions\UpdateOrderStatusNote;
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
use App\Http\Requests\UpdateOrderStatusNoteRequest;
use App\Models\Client;
use App\Models\DurationOfWork;
use App\Models\ExtraWork;
use App\Models\InstallationTeam;
use App\Models\ProductConfig;
use App\Models\TravelCost;
use App\Models\TypeOfHousing;
use App\Models\TypeOfProduct;
use App\Models\TypeOfWork;
use App\Models\User;
use App\Traits\Product;
use Doctrine\DBAL\Types\Type;
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
            Order::with(['orderStatus'])
              ->filter($request->only(['text']))
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
          'installation_teams' => InstallationTeam::with(['user'])->get(),
          'supervisors' => User::role(RoleEnum::SUPERVISOR->value)->get(),
          'methods_of_payment' => [
            MethodOfPayment::CASH->value,
            MethodOfPayment::FINANCED->value
          ],
          'services' => [
            Service::INSTALLATION->value,
            Service::DELIVERY->value
          ],
          'travel_costs' => TravelCost::all(),
          'duration_of_works' => DurationOfWork::all(),
          'products_config' => ProductConfig::all(),
          'type_of_products' => TypeOfProduct::all(),
          'extra_works' => ExtraWork::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEstimateRequest $storeEstimateRequest, CreateEstimate $createEstimate)
    {
        $estimate = $createEstimate->handle($storeEstimateRequest);
        return redirect()->route('estimate.show', ['estimate' => $estimate->id]);
    }

    public function history(Order $order) {
      $order->load(['orderStatus' => function(Builder $query) {
        $query->orderBy('id', 'desc');
      }, 'orderStatus.user']);
      return response()->json($order->orderStatus);
    }
}
