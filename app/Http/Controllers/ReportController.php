<?php

namespace App\Http\Controllers;

use App\Actions\CreateBiweekly;
use App\Actions\UpdateBiweekly;
use App\Actions\UpdatePaymentInstaller;
use App\Enum\HistoryPaymentEnum;
use App\Enum\InstallerPaymentStatusEnum;
use App\Enum\MethodOfPayment;
use App\Enum\ContactSourceEnum;
use App\Enum\LostReasonfrontdeskEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\ProductLineEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Enum\StatusUserEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Exports\InstallerExport;
use App\Exports\InstallerConfirmedSummaryExport;
use App\Exports\DailyOrderStatusSummaryExport;
use App\Exports\AccountingStatusSummaryExport;
use App\Exports\SalesAppointmentsBySellerExport;
use App\Exports\MarketingReportExport;
use App\Exports\OwnerAssignedSummaryExport;
use App\Exports\OverdueStageOrdersExport;
use App\Exports\ReplannedOrdersSummaryExport;
use App\Exports\StatusTransitionAverageExport;
use App\Exports\SupervisorExport;
use App\Exports\SupervisorExportPayment;
use App\Exports\SupervisorAssignedSummaryExport;
use App\Http\Requests\StoreInstallerPaymentRequest;
use App\Http\Resources\InstallationTeamCollection;
use App\Jobs\SendGmailEmail;
use App\Mail\InstallationPaidEmail;
use App\Mail\InstallationPayment as MailInstallationPayment;
use App\Mail\InstallationPaymentEmail;
use App\Models\Biweekly;
use App\Models\Client;
use App\Models\HistoryPendingPayment;
use App\Models\InstallationPayment;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentExtraField;
use App\Models\Setting;
use App\Services\OverdueStageOrdersReportService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use App\Rules\ValidateInstallationPayment;
use App\Models\OrderStatus;
use Barryvdh\LaravelIdeHelper\Method;
use Inertia\Inertia;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Twilio\Rest\Api\V2010\Account\Call\PaymentInstance;
use Illuminate\Contracts\Validation\ValidationRule;
use Barryvdh\DomPDF\Facade\Pdf;
use Faker\Provider\ar_EG\Payment;
use Google\Service\AndroidEnterprise\Install;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
  use \App\Traits\Reports; //Todo: remove this to a trait

  


  public function supervisor(Request $request)
  { 
    return Inertia::render('Report/Supervisor', [
      'users' => User::whereHas('roles', function ($query) {
        $query->where('name', 'supervisor'); // Cambia 'name' si usas otro campo para el nombre del rol
      })
        ->filter($request->only(['text']))
        ->orderBy('name')
        ->paginate()
        ->withQueryString()
    ]);
  }

  public function showSupervisor($id)
  {
    // Obtener las órdenes por supervisor
    $orders = $this->getOrdersBySupervisor($id);
    //dd($orders);

    // Obtener los parámetros de filtro de la solicitud (request)
    $status = request()->get('status');
    $name = request()->get('name');
    $startDate = request()->get('start_date');
    $endDate = request()->get('end_date');

    // Filtrar las órdenes por estado
  /*  if ($orders instanceof EloquentBuilder || $orders instanceof QueryBuilder) {
      // Es una consulta, puedes aplicar where y usar toSql
      $query = $orders->where('supervisor_payment_status', 'like', '%' . $status . '%');
      dd($query->toSql(), $query->getBindings()); // Depuración
      $orders = $query->get();
    } elseif ($orders instanceof Collection) {
      // Es una colección, debes filtrar en memoria
      $orders = $orders->filter(function ($order) use ($status) {
        return stripos($order['supervisor_payment_status'], $status) !== false;
      });
    } else {
      dd('Tipo desconocido:', get_class($orders));
    }*/
    if ($orders instanceof EloquentBuilder || $orders instanceof QueryBuilder) {
      $query = $orders;
  
      if ($status) {
          $query = $query->where('supervisor_payment_status', 'like', '%' . $status . '%');
      } else {
          $query = $query->whereIn('supervisor_payment_status', [
              SupervisorPaymentStatusEnum::OPEN->value,
              SupervisorPaymentStatusEnum::PENDING->value,
          ]);
      }
  
      $orders = $query->get();
       
  } elseif ($orders instanceof Collection) {
      
      $orders = $orders->filter(function ($order) use ($status) {
      
          if ($status) {
              return stripos($order['supervisor_payment_status'], $status) !== false;
          } else {
              return in_array($order['supervisor_payment_status'], [
                  SupervisorPaymentStatusEnum::OPEN->value,
                  SupervisorPaymentStatusEnum::PENDING->value,
              ]);
          }
      });
  }
  //dd($orders);

    if ($name) {
      $orders = $orders->filter(function ($order) use ($name) {
        return stripos($order['name'], $name) !== false; // Filtro por nombre
      });
    }
    //dd($orders);

    if ($startDate) {
      $orders = $orders->where('supervisor_payment_date', '>=', $startDate);
    }

    if ($endDate) {
      $orders = $orders->where('supervisor_payment_date', '<=', $endDate);
    }
    //dd($orders);

    // Retornar la vista con las órdenes filtradas
    return Inertia::render('Report/ShowSupervisor', [
      'orders' => $orders->values()->toArray(),
      'supervisor' => User::find($id),
      'statuses' => [
        SupervisorPaymentStatusEnum::OPEN->value,
        SupervisorPaymentStatusEnum::PENDING->value,
        SupervisorPaymentStatusEnum::CLOSED->value,
        SupervisorPaymentStatusEnum::NO_PAID->value,
      ],
      'filters' => [
        'status' => $status,
        'name' => $name,
        'start_date' => $startDate,
        'end_date' => $endDate,
      ],
    ]);
  }

  public function export(Request $request, User $user)
  {
    return Excel::download(
      new SupervisorExport($user->id),
      'Supervisor ' . $user->name . '.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }


  public function exportPaymentSupervisor(Request $request, $id, User $user )
  { //dd( $request->all());
    return Excel::download(
      new SupervisorExportPayment(
            $id,
            $request->status,
            $request->name,
            $request->start_date,
            $request->end_date
    ),
      'Supervisor ' . $user->name . '.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function showSupervisorReport($id)
  {
    // Obtener las órdenes por supervisor
    $orders = $this->getOrdersBySupervisorReport($id);

    //dd($orders);

    // Obtener los parámetros de filtro de la solicitud (request)
    $status = request()->get('status');
    $name = request()->get('name');
    $startDate = request()->get('start_date');
    $endDate = request()->get('end_date');

    //dd($status, $name, $startDate, $endDate);

    
    if ($orders instanceof EloquentBuilder || $orders instanceof QueryBuilder) {
      $query = $orders;
  
      if ($status) {
          $query = $query->where('status', 'like', '%' . $status . '%');
      } else {
          $query = $query->whereIn('status', [  
              OrderStatusEnum::CONFIRMED->value,
              OrderStatusEnum::EXECUTION->value,
              OrderStatusEnum::DELIVERY_CONFIRMED->value,
              OrderStatusEnum::COMPLETE->value,
              OrderStatusEnum::FINAL_COLLECT->value,
              OrderStatusEnum::INSPECTION->value,
              OrderStatusEnum::SUPERVISION->value,
              OrderStatusEnum::ON_HOLD->value,
              OrderStatusEnum::RESCHEDULE->value,
              OrderStatusEnum::FINISH->value,
              OrderStatusEnum::SERVICE->value,
              OrderStatusEnum::FINAL_INSPECTION->value,  
          ]);
      }
  
      $orders = $query->get();


  } elseif ($orders instanceof Collection) {
      $orders = $orders->filter(function ($order) use ($status) {
          if ($status) {
              return stripos($order['status'], $status) !== false;
          } else {
              return in_array($order['status'], [
                OrderStatusEnum::CONFIRMED->value,
              OrderStatusEnum::EXECUTION->value,
              OrderStatusEnum::DELIVERY_CONFIRMED->value,
              OrderStatusEnum::COMPLETE->value,
              OrderStatusEnum::FINAL_COLLECT->value,
              OrderStatusEnum::INSPECTION->value,
              OrderStatusEnum::SUPERVISION->value,
              OrderStatusEnum::ON_HOLD->value,
              OrderStatusEnum::RESCHEDULE->value,
              OrderStatusEnum::FINISH->value,
              OrderStatusEnum::SERVICE->value,
              OrderStatusEnum::FINAL_INSPECTION->value,  
              ]);
          }
      });
  }

    if ($name) {
      $orders = $orders->filter(function ($order) use ($name) {
        return stripos($order['name'], $name) !== false; // Filtro por nombre
      });
    }
    //dd($orders);

    if ($startDate) {
      $orders = $orders->where('supervisor_payment_date', '>=', $startDate);
    }

    if ($endDate) {
      $orders = $orders->where('supervisor_payment_date', '<=', $endDate);
    }
    //dd($orders);

    // Retornar la vista con las órdenes filtradas
    return Inertia::render('Report/ShowSupervisorReport', [
      'orders' => $orders->values()->toArray(),
      'supervisor' => User::find($id),
      'statuses' => [
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::DELIVERY_CONFIRMED->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::ON_HOLD->value,
        OrderStatusEnum::RESCHEDULE->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        OrderStatusEnum::FINAL_INSPECTION->value,  
      ],
      'filters' => [
        'status' => $status,
        'name' => $name,
        'start_date' => $startDate,
        'end_date' => $endDate,
      ],
    ]);
  }
  


  public function showInstaller($id)
  {
    $status = request()->get('status');
    $orderStatus = request()->get('order_status');
    $startDate = request()->get('start_date');
    $endDate = request()->get('end_date');
    //dd($startDate, $endDate );
    // Obtener las órdenes por supervisor
    $orders = $this->getOrdersByInstaller($id, $status, $startDate, $endDate, $orderStatus);
    //dd($orders);

    $name = request()->get('name');
    $paymentDate = request()->get('payment_date');
    //$biweeklys = Biweekly::get();
    $biweeklys = Biweekly::whereDoesntHave('installationPayments', function ($query) use ($id) {
      $query->where('installation_team_id', $id);
    })->orderBy('id', 'desc')->get();


    $companyName = InstallationTeam::where('user_id', $id)->value('company_name');

    //dd($companyName);


    if ($name) {
      $orders = $orders->filter(function ($order) use ($name) {
        return stripos($order['name'], $name) !== false; // Filtro por nombre
      });
    }
    //dd($orders);
    // Retornar la vista con las órdenes filtradas
    return Inertia::render('Report/ShowInstaller', [
      'orders' => $orders->values()->toArray(),
      'installer' => User::find($id),
      'companyName' => $companyName,
      'biweeklys' => $biweeklys->toArray(),
      'statuses' => [
        InstallerPaymentStatusEnum::OPEN->value,
        InstallerPaymentStatusEnum::PARTIALLY_PAID->value,
        InstallerPaymentStatusEnum::FULLY_PAID->value,
      ],
      'orderStatuses' => [
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::ON_HOLD->value,
        OrderStatusEnum::RESCHEDULE->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::SERVICE->value,
      ],
    ]);
  }

  public function installer(Request $request)
  {     // dd(InstallationTeam::first());
    return Inertia::render('Report/Installer', [
      'installation_teams' => new InstallationTeamCollection(
        InstallationTeam::filter($request->only(['text']))
        ->join('users', 'users.id', '=', 'installation_teams.user_id')
        ->where('users.status', StatusUserEnum::ACTIVE->value)
        ->orderBy('users.name', 'asc')
        ->select('installation_teams.*') // Importante: evita conflictos en los campos seleccionados
        ->paginate()
        ->withQueryString()
      )
    ]);
  }

  public function installerPayments(Request $request, $id)
  {
    $search = trim((string) $request->get('search', ''));
    $installer = User::findOrFail($id);
    $companyName = InstallationTeam::where('user_id', $id)->value('company_name');

    $payments = InstallationPayment::with([
      'order.owners',
      'order.installationTeams.user',
      'order.orderProducts',
      'order.travelCost',
      'biweekly',
      'installationTeam',
    ])
      ->where('installation_team_id', $id)
      ->when($search !== '', function ($query) use ($search) {
        $query->whereHas('order', function ($subQuery) use ($search) {
          $subQuery->where('name', 'like', "%{$search}%")
            ->orWhere('order_number', 'like', "%{$search}%");
        });
      })
      ->orderByRaw('biweekly_id IS NULL')
      ->orderByDesc('biweekly_id')
      ->orderByDesc('payment_date')
      ->orderByDesc('created_at')
      ->get();

    $orders = $payments
      ->groupBy('order_id')
      ->map(function ($orderPayments) use ($id) {
        $order = $orderPayments->first()->order;
        $otherInstallerPayments = InstallationPayment::with(['biweekly', 'installationTeam'])
          ->where('order_id', $order->id)
          ->where('installation_team_id', '!=', $id)
          ->orderByDesc('biweekly_id')
          ->orderByDesc('payment_date')
          ->orderByDesc('created_at')
          ->get();

        return [
          'id' => $order->id,
          'name' => $order->name,
          'order_number' => $order->order_number,
          'status' => $order->status,
          'amount' => $order->getGrandTotalPrice(),
          'owners' => $order->owners->map(fn ($owner) => [
            'id' => $owner->id,
            'name' => $owner->name,
          ])->values(),
          'current_installers' => $order->installationTeams->map(fn ($team) => [
            'id' => $team->user_id,
            'name' => $team->user->name ?? '',
            'company_name' => $team->company_name,
          ])->values(),
          'payments' => $orderPayments->map(function ($payment) {
            return [
              'id' => $payment->id,
              'installer_id' => $payment->installation_team_id,
              'installer_name' => $payment->installationTeam->name ?? '',
              'percentage_payment' => (float) $payment->percentage_payment,
              'installer_payment' => (float) $payment->installer_payment,
              'extra_work' => (float) $payment->extra_work,
              'extra_discount' => (float) $payment->extra_discount,
              'other_cost_installer' => (float) $payment->other_cost_installer,
              'total_payment' => (float) $payment->installer_payment + (float) $payment->extra_work - (float) $payment->extra_discount + (float) $payment->other_cost_installer,
              'payment_status' => $payment->payment_status,
              'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('Y-m-d') : null,
              'notes' => $payment->notes,
              'responsible_extra_work' => $payment->responsible_extra_work,
              'biweekly' => $payment->biweekly ? [
                'id' => $payment->biweekly->id,
                'start_biweekly_period' => $payment->biweekly->start_biweekly_period
                  ? Carbon::parse($payment->biweekly->start_biweekly_period)->format('Y-m-d')
                  : null,
                'end_biweekly_period' => $payment->biweekly->end_biweekly_period
                  ? Carbon::parse($payment->biweekly->end_biweekly_period)->format('Y-m-d')
                  : null,
              ] : null,
            ];
          })->values(),
          'other_installer_payments' => $otherInstallerPayments->map(function ($payment) {
            return [
              'id' => $payment->id,
              'installer_id' => $payment->installation_team_id,
              'installer_name' => $payment->installationTeam->name ?? '',
              'percentage_payment' => (float) $payment->percentage_payment,
              'installer_payment' => (float) $payment->installer_payment,
              'extra_work' => (float) $payment->extra_work,
              'extra_discount' => (float) $payment->extra_discount,
              'other_cost_installer' => (float) $payment->other_cost_installer,
              'total_payment' => (float) $payment->installer_payment + (float) $payment->extra_work - (float) $payment->extra_discount + (float) $payment->other_cost_installer,
              'payment_status' => $payment->payment_status,
              'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('Y-m-d') : null,
              'notes' => $payment->notes,
              'responsible_extra_work' => $payment->responsible_extra_work,
              'biweekly' => $payment->biweekly ? [
                'id' => $payment->biweekly->id,
                'start_biweekly_period' => $payment->biweekly->start_biweekly_period
                  ? Carbon::parse($payment->biweekly->start_biweekly_period)->format('Y-m-d')
                  : null,
                'end_biweekly_period' => $payment->biweekly->end_biweekly_period
                  ? Carbon::parse($payment->biweekly->end_biweekly_period)->format('Y-m-d')
                  : null,
              ] : null,
            ];
          })->values(),
          'total_paid' => $orderPayments->sum('installer_payment'),
          'total_extras' => $orderPayments->sum('extra_work'),
          'total_discounts' => $orderPayments->sum('extra_discount'),
          'total_other_costs' => $orderPayments->sum('other_cost_installer'),
          'total_payment' => $orderPayments->sum(fn ($payment) => (float) $payment->installer_payment + (float) $payment->extra_work - (float) $payment->extra_discount + (float) $payment->other_cost_installer),
          'other_installers_total_payment' => $otherInstallerPayments->sum(fn ($payment) => (float) $payment->installer_payment + (float) $payment->extra_work - (float) $payment->extra_discount + (float) $payment->other_cost_installer),
        ];
      })
      ->sortByDesc(fn ($order) => $order['payments']->first()['payment_date'] ?? '')
      ->values();

    return Inertia::render('Report/InstallerPayments', [
      'installer' => $installer,
      'companyName' => $companyName,
      'orders' => $orders->toArray(),
      'filters' => [
        'search' => $search,
      ],
    ]);
  }

  public function editReportInstaller($id, $installation_team)
  {   // Cargar la orden junto con los campos relacionados

    $order = Order::with([
      'paymentExtraFields' => function ($query) use ($installation_team) {
        $query->where('installation_team_id', $installation_team);
      }, // Cargar los paymentExtraFields
      'user', // Cargar el usuario relacionado
      'installationTeams.user', // Cargar los equipos de instalación y sus usuarios
      'owners',
      'supervisor' // Cargar los propietarios
    ])->findOrFail($id);

    $biweeklys = Biweekly::orderBy('id', 'desc')->get();

    $amount = $order->getGrandTotalPrice();

    $payment = InstallationPayment::with('installationTeam')
      ->where('order_id', $id)
      ->get()
      ->map(function ($payment) {
        return [
          ...$payment->toArray(),
          'installer_name' => $payment->installationTeam->name ?? '',
        ];
      });


    //dd($payment);
    // Retornar la vista con los datos
    return Inertia::render('Report/EditReportInstaller', [
      'order' => $order, // Pasamos los datos de la orden
      'installation_team_id' => $installation_team,
      'amount' => $amount,
      'biweeklys' => $biweeklys->toArray(),
      'payment' => $payment->values()->toArray(),
      'installer_payment_status' => [
        InstallerPaymentStatusEnum::OPEN->value,
        //InstallerPaymentStatusEnum::PENDING->value,
        InstallerPaymentStatusEnum::PARTIALLY_PAID->value,
        InstallerPaymentStatusEnum::FULLY_PAID->value,
        //InstallerPaymentStatusEnum::CLOSED->value,
      ],
      'payment_status' => [
        PaymentStatusEnum::REVIEW->value,
        PaymentStatusEnum::PAID->value,
      ],

    ]);
  }

  public function  updateInstallerReport(Request $request)
  {   // Cargar la orden junto con los campos relacionados

      $data = [
        'order_id' => $request->input('order_id'),
        'installation_team_id' => $request->input('installation_team_id'),
        'installer_payment_status' => $request->input('installer_payment_status'),
      ];

    // Si el id es 0, se crea una nueva fila
    if ($request->input('id') == 0) {
      PaymentExtraField::create($data);
    } else {
      // Si el id existe, se actualiza el registro
      PaymentExtraField::updateOrCreate(
        ['id' => $request->input('id')], // Condición para buscar el registro por ID
        $data // Si existe, actualiza con estos datos
      );
    }
    return redirect()->route('report.show_installer', $request->input('installation_team_id'))
      ->with('success', 'Order updated successfully.');
  }

  public function  updateInstallerPayment(StoreInstallerPaymentRequest $request, UpdatePaymentInstaller $updatePaymentInstaller)
  {
    $updatePaymentInstaller->handle($request);
    return redirect()->back()->with('success', 'Order updated successfully.');
  }

  public function  editInstallerPayment($id)
  {
    $paymentInstaller = InstallationPayment::findOrFail($id);
    return response()->json($paymentInstaller);
  }

  public function exportPaymentInstaller($id, $biweekly)
  {  
        return Excel::download(
          new InstallerExport($id, $biweekly),
          'Biweekly ' . $id . ' to ' . $id . '.xlsx',
          \Maatwebsite\Excel\Excel::XLSX
        );
  }

  public function biweeklyPayment($id, $biweekly)
  {   if (!Biweekly::where('id', $biweekly)->exists()) {
      return redirect()->back()->with('error', 'The biweekly period does not exist.');
    }
    $paymentExist = InstallationPayment::where('biweekly_id', $biweekly)->where('installation_team_id', $id)->count();
    if ($paymentExist > 0) {
      return redirect()->back()->with('error', 'The payment for this biweekly period already exists.');
    }
    $orders = $this->getOrdersByInstaller($id, $status = null, $startDate = null, $endDate = null, $orderStatu = null);
    
    DB::beginTransaction();
    try {
      HistoryPendingPayment::create([
        'biweekly_id' => $biweekly,
        'installation_team_id' => $id,
        'data' => $orders,
        'type_history'=> HistoryPaymentEnum::INSTALLER->value
      ]);

      $ordersToPay = $orders->where('total_payment_amount', '>', 0);
      $ordersToPay->each(function ($order) use ($biweekly, $id) {
        //dd($order);
        foreach ($order['installation_payments'] as $payment) {
          $installationPayment = InstallationPayment::find($payment['id']);
         
          if ($installationPayment->payment_status == PaymentStatusEnum::REVIEW->value && $installationPayment->biweekly_id == null) {
            //dd('entro');
            $installationPayment->update([
              'biweekly_id' => $biweekly,
              'payment_status' => PaymentStatusEnum::PAID->value,
              'payment_date' => Carbon::now(),
            ]);
          }
        }

        $paymentPercentage = InstallationPayment::where('order_id', $order['id'])
          ->where('installation_team_id', $id)
          ->where('payment_status', PaymentStatusEnum::PAID->value)
          ->sum('percentage_payment');

        $getAllIntallerPaymentAmount = InstallationPayment::where('order_id', $order['id'])
          ->where('payment_status', PaymentStatusEnum::PAID->value)
          ->sum('installer_payment');
        
        $paymentExtraFields = PaymentExtraField::find($order['payment_extra_fields']['id']);
        if ($getAllIntallerPaymentAmount >= $order['amount']) {
          $paymentExtraFields->update([
            'installer_payment_status' => InstallerPaymentStatusEnum::FULLY_PAID->value,
          ]);
        } else {
          $paymentExtraFields->update([
            'installer_payment_status' => InstallerPaymentStatusEnum::PARTIALLY_PAID->value,
          ]);

          $pendingPaymentPercent = 100 - $paymentPercentage;
          $requiresPermit = (bool) $order['city_permits'];
          $canCreateFinalInstallerPayment = $paymentPercentage >= 80
            && $order['status'] == OrderStatusEnum::COMPLETE->value
            && $order['walk_trough'] == 1
            && $order['final_payment_installation'] == 1
            && (! $requiresPermit || $order['inspection'] == 1);
          $canCreateFullInstallerPayment = $paymentPercentage == 0
            && $pendingPaymentPercent == 100
            && $order['status'] == OrderStatusEnum::COMPLETE->value
            && $order['walk_trough'] == 1
            && $order['final_payment_installation'] == 1
            && (! $requiresPermit || $order['inspection'] == 1);

            //dd( $pendingPaymentPercent);

          if($order['status'] == OrderStatusEnum::INSPECTION->value || $order['pre_inspection'] == 0 || $order['partial_payment_installation'] == 0){
            $pendingPaymentPercent = 0;
          }
          if(! $canCreateFinalInstallerPayment && ! $canCreateFullInstallerPayment){
            $pendingPaymentPercent = 0;
          }
          InstallationPayment::create([
            'order_id' => $order['id'],
            'installation_team_id' => $id,
            'biweekly_id' => null,
            'percentage_payment' => $pendingPaymentPercent,
            'installer_payment' => $order['amount'] * $pendingPaymentPercent / 100,
            'payment_status' => PaymentStatusEnum::REVIEW->value,
            'payment_date' => null,
            'extra_work' => 0,
            'extra_discount' => 0,
            'other_cost_installer' => 0,
          ]);
        }
      });

      DB::commit();
      return redirect()->back()->with('success', 'The payment for this biweekly period has been created successfully.');
    } catch (\Exception $e) {
      DB::rollBack();
      //dd($e);
      return redirect()->back()->with('error', 'An error occurred while creating the payment for this biweekly period.');
    }
    
   }

    public function getPaymentListInstaller($id,$biweekly)
    {  
        $orders = $this->getOrdersByInstaller($id, $status=null , $startDate=null, $endDate=null, $orderStatu=null);
        $installerName = $orders->first()['installer'] ?? '';
        $companyName = $orders->first()['company_name'] ?? '';
        $biweekly = Biweekly::find((int)$biweekly);
        $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
        $pdf = Pdf::loadView('pdf.payment-list-orders', ['orders' => $orders, 'company' => $companyName, 'installer' => $installerName,  'biweeklyTitle' => $biweeklyTitle])->setPaper('A2', 'landscape');
        $pdfName = 'pdf.payment-list-orders' .$installerName . '.pdf';
        return $pdf->stream($pdfName);
    
    }

    public function sendPaymentInstaller($id,$biweekly)
    {  
        if (!Biweekly::where('id', $biweekly)->exists()) {
          return redirect()->back()->with('error', 'The biweekly period does not exist.');
        }
  
        $orders = $this->getOrdersByInstaller($id, $status=null , $startDate=null, $endDate=null, $orderStatu=null);
        $adminEmailsConfig = config('custom.admin_emails_payment');
        $adminEmails = explode(',', $adminEmailsConfig); // Convierte la cadena en array
        $user = User::find($id);
        $users = array_merge([$user->email], $adminEmails);

         // Une los correos en un solo array
        $installerName = $orders->first()['installer'] ?? '';
        $companyName = $orders->first()['company_name'] ?? '';
        $biweekly = Biweekly::find((int)$biweekly);
        $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
        $installationPaymentEmail = new InstallationPaymentEmail($orders, $installerName, $companyName, $biweeklyTitle);
        foreach ($users as $user) {
          SendGmailEmail::dispatch( $user, $installationPaymentEmail)->onQueue('emails');
        }
        return redirect()->back()->with('success', 'The email was successfully sent to the installer.');
    } 

    public function sendPaidInstaller($id,$biweekly)
    {  
            if (!Biweekly::where('id', $biweekly)->exists()) {
              return redirect()->back()->with('error', 'The biweekly period does not exist.');
            }
            $accountings = User::role([RoleEnum::ACCOUNTING->value])->get();
            $orders = $this->getOrdersByInstaller($id, $status=null , $startDate=null, $endDate=null, $orderStatu=null);
            $adminEmailsConfig = config('custom.admin_emails_payment');
            $adminEmails = explode(',', $adminEmailsConfig); // Convierte la cadena en array
            $user = User::find($id);
            $users = array_merge([$user->email], $adminEmails); // Une los correos en un solo array
            $users = array_merge($users, $accountings->pluck('email')->toArray());
            //dd($users);
            $installerName = $orders->first()['installer'] ?? '';
            $companyName = $orders->first()['company_name'] ?? '';
            $biweekly = Biweekly::find((int)$biweekly);
            $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
            $installationPaymentEmail = new InstallationPaidEmail($orders, $installerName, $companyName, $biweeklyTitle);
            foreach ($users as $user) {
              //dd($user, $users);
              SendGmailEmail::dispatch( $user, $installationPaymentEmail)->onQueue('emails');
            }
            return redirect()->back()->with('success', 'The email was successfully sent to the installer.');
        } 

  public function productSummary(Request $request)
  {
      // $user = auth()->user();
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);

          /*$filteredOrderIds = Order::with(['orderStatus' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }])->get()->filter(function ($order) {
            $statuses = $order->orderStatus->pluck('status');
            return $statuses->contains('PLANNED');
        })->pluck('id');*/
        $filteredOrderIds = $this->resolveUniqueOrderIdsByStatus(
          OrderStatusEnum::CONFIRMED->value,
          $startDate,
          $endDate
        );
        
            //dd($filteredOrderIds);
            $totalOrders = $filteredOrderIds->count();
            //dd($totalOrders);
            $rawData = OrderProduct::select(
              'type_of_product_id',
              DB::raw('SUM(qty) as total_qty'),
              DB::raw('SUM(storefront_area) as total_storefront_area')
          )
          ->whereIn('order_id', $filteredOrderIds)
          ->whereNull('deleted_at')
          ->whereIn('type_of_product_id', [1, 2, 3])
          ->groupBy('type_of_product_id')
          ->with('typeOfProduct:id,name')
          ->get();

          //dd($rawData);

          $productSummary = $rawData->map(function ($item) use ($totalOrders) {
            return [
                'product_type_id' => $item->type_of_product_id,
                'product_type' => $item->typeOfProduct->name ?? 'N/A',
                'product_count' => $item->total_qty,
                'storefront_area' => $item->type_of_product_id == 3 ? $item->total_storefront_area : null,
                'total_filtered_orders' => $totalOrders,
            ];
        });

        //dd($productSummary);

    return Inertia::render('Report/ProductSummary', [
        'productSummary' => $productSummary,
        'startDate' => $startDate->toDateString(),
        'endDate' => $endDate->toDateString(),
    ]);
}

  public function orderStatusSummary(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);

    $statuses = [
      OrderStatusEnum::PLANNED->value,
      OrderStatusEnum::CONFIRMED->value,
      OrderStatusEnum::COMPLETE->value,
    ];

    $periodDays = $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1;
    $defaultPreviousEndDate = $startDate->copy()->subDay()->endOfDay();
    $defaultPreviousStartDate = $defaultPreviousEndDate->copy()->subDays($periodDays - 1)->startOfDay();

    if ($request->filled('previous_start_date') && $request->filled('previous_end_date')) {
      $previousStartDate = Carbon::parse($request->previous_start_date)->startOfDay();
      $previousEndDate = Carbon::parse($request->previous_end_date)->endOfDay();
    } elseif ($request->filled('previous_start_date')) {
      $previousStartDate = Carbon::parse($request->previous_start_date)->startOfDay();
      $previousEndDate = $previousStartDate->copy()->addDays($periodDays - 1)->endOfDay();
    } elseif ($request->filled('previous_end_date')) {
      $previousEndDate = Carbon::parse($request->previous_end_date)->endOfDay();
      $previousStartDate = $previousEndDate->copy()->subDays($periodDays - 1)->startOfDay();
    } else {
      $previousStartDate = $defaultPreviousStartDate;
      $previousEndDate = $defaultPreviousEndDate;
    }

    $buildCounts = function (Carbon $rangeStart, Carbon $rangeEnd) use ($statuses) {
      $plannedOrderIds = $this->resolveUniqueOrderIdsByStatus(
        OrderStatusEnum::PLANNED->value,
        $rangeStart,
        $rangeEnd
      );

      $confirmedOrderIds = $this->resolveUniqueOrderIdsByStatus(
        OrderStatusEnum::CONFIRMED->value,
        $rangeStart,
        $rangeEnd
      );

      $completedOrderIds = $this->resolveUniqueOrderIdsByStatus(
        OrderStatusEnum::COMPLETE->value,
        $rangeStart,
        $rangeEnd
      );

      $pickupOrDeliveryQualifiedOrderIds = $plannedOrderIds
        ->merge($confirmedOrderIds)
        ->unique()
        ->values();

      $confirmedCompletedOrderIds = $confirmedOrderIds
        ->intersect($completedOrderIds)
        ->values();

      $confirmedCount = $confirmedOrderIds->isEmpty()
        ? 0
        : Order::query()
          ->join('users', 'users.id', '=', 'orders.supervisor_id')
          ->whereNull('orders.deleted_at')
          ->whereNotNull('orders.supervisor_id')
          ->where('users.status', StatusUserEnum::ACTIVE->value)
          ->whereIn('orders.id', $confirmedOrderIds->all())
          ->count();

      $confirmedCompletedCount = $confirmedCompletedOrderIds->isEmpty()
        ? 0
        : Order::query()
          ->join('users', 'users.id', '=', 'orders.supervisor_id')
          ->whereNull('orders.deleted_at')
          ->whereNotNull('orders.supervisor_id')
          ->where('users.status', StatusUserEnum::ACTIVE->value)
          ->whereIn('orders.id', $confirmedCompletedOrderIds->all())
          ->count();

      $pickupOrDeliveryConfirmedCount = $pickupOrDeliveryQualifiedOrderIds->isEmpty()
        ? 0
        : Order::query()
          ->whereNull('deleted_at')
          ->whereNull('supervisor_id')
          ->whereIn('service', [ServiceEnum::PICKUP->value, ServiceEnum::DELIVERY->value])
          ->whereIn('id', $pickupOrDeliveryQualifiedOrderIds->all())
          ->count();

      $pickupOrDeliveryCompletedCount = $pickupOrDeliveryQualifiedOrderIds->isEmpty()
        ? 0
        : Order::query()
          ->whereNull('deleted_at')
          ->whereNull('supervisor_id')
          ->whereIn('service', [ServiceEnum::PICKUP->value, ServiceEnum::DELIVERY->value])
          ->whereIn('id', $pickupOrDeliveryQualifiedOrderIds->all())
          ->whereIn('id', $completedOrderIds->all())
          ->count();

      $confirmedCount += $pickupOrDeliveryConfirmedCount;
      $confirmedCompletedCount += $pickupOrDeliveryCompletedCount;

      $counts = collect($statuses)->mapWithKeys(function ($status) use ($rangeStart, $rangeEnd, $confirmedCompletedCount, $confirmedCount) {
        if ($status === OrderStatusEnum::COMPLETE->value) {
          return [$status => $confirmedCompletedCount];
        }

        if ($status === OrderStatusEnum::CONFIRMED->value) {
          return [$status => $confirmedCount];
        }

        $count = $this->resolveUniqueOrderIdsByStatus($status, $rangeStart, $rangeEnd)->count();

        return [$status => $count];
      })->all();

      $completedFromConfirmedPercentage = $confirmedCount > 0
        ? round(($confirmedCompletedCount / $confirmedCount) * 100, 2)
        : 0;

      return [
        'counts' => $counts,
        'confirmedCount' => $confirmedCount,
        'completedConfirmedCount' => $confirmedCompletedCount,
        'completedFromConfirmedPercentage' => $completedFromConfirmedPercentage,
      ];
    };

    $currentPeriodData = $buildCounts($startDate, $endDate);
    $previousPeriodData = $buildCounts($previousStartDate, $previousEndDate);

    $statusSummary = collect($statuses)->map(function ($status) use ($currentPeriodData, $previousPeriodData) {
      $currentCount = $currentPeriodData['counts'][$status] ?? 0;
      $previousCount = $previousPeriodData['counts'][$status] ?? 0;
      $delta = $currentCount - $previousCount;

      $percentageChange = $previousCount > 0
        ? round(($delta / $previousCount) * 100, 2)
        : null;
      $representationPercentage = $previousCount > 0
        ? round(($currentCount / $previousCount) * 100, 2)
        : null;

      return [
        'status' => $status,
        'count' => $currentCount,
        'current_count' => $currentCount,
        'previous_count' => $previousCount,
        'delta' => $delta,
        'percentage_change' => $percentageChange,
        'representation_percentage' => $representationPercentage,
      ];
    });

    return Inertia::render('Report/OrderStatusSummary', [
      'statusSummary' => $statusSummary,
      'confirmedCount' => $currentPeriodData['confirmedCount'],
      'completedConfirmedCount' => $currentPeriodData['completedConfirmedCount'],
      'completedFromConfirmedPercentage' => $currentPeriodData['completedFromConfirmedPercentage'],
      'previousConfirmedCount' => $previousPeriodData['confirmedCount'],
      'previousCompletedConfirmedCount' => $previousPeriodData['completedConfirmedCount'],
      'previousCompletedFromConfirmedPercentage' => $previousPeriodData['completedFromConfirmedPercentage'],
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
      'previousStartDate' => $previousStartDate->toDateString(),
      'previousEndDate' => $previousEndDate->toDateString(),
    ]);
  }

  public function plannedToCompleteAverage(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $businessType = $this->resolvePlannedToCompleteBusinessType($request);
    $transitionType = $this->resolvePlannedToCompleteTransitionType($request);
    $serviceType = $this->resolvePlannedToCompleteServiceType($request);
    $data = $this->buildPlannedToCompleteAverageData($startDate, $endDate, $businessType, $transitionType, $serviceType);

    return Inertia::render('Report/PlannedToCompleteAverage', $data);
  }

  public function plannedToCompleteAveragePdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $businessType = $this->resolvePlannedToCompleteBusinessType($request);
    $transitionType = $this->resolvePlannedToCompleteTransitionType($request);
    $serviceType = $this->resolvePlannedToCompleteServiceType($request);
    $data = $this->buildPlannedToCompleteAverageData($startDate, $endDate, $businessType, $transitionType, $serviceType);

    $pdf = Pdf::loadView('pdf.status-transition-average', $data)->setPaper('A4', 'landscape');
    $pdfName = 'status-transition-average.pdf';

    return $pdf->stream($pdfName);
  }

  public function plannedToCompleteAverageExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $businessType = $this->resolvePlannedToCompleteBusinessType($request);
    $transitionType = $this->resolvePlannedToCompleteTransitionType($request);
    $serviceType = $this->resolvePlannedToCompleteServiceType($request);
    $data = $this->buildPlannedToCompleteAverageData($startDate, $endDate, $businessType, $transitionType, $serviceType);

    return Excel::download(
      new StatusTransitionAverageExport($data),
      'Status Transition Average.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function accountingStatusSummary(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $selectedStatus = $this->resolveAccountingStatus($request);
    $data = $this->buildAccountingStatusSummaryData($startDate, $endDate, $selectedStatus);

    return Inertia::render('Report/AccountingStatusSummary', $data);
  }

  public function accountingStatusSummaryPdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $selectedStatus = $this->resolveAccountingStatus($request);
    $data = $this->buildAccountingStatusSummaryData($startDate, $endDate, $selectedStatus);
    $pdf = Pdf::loadView('pdf.accounting-status-summary', $data)->setPaper('A4', 'landscape');
    $pdfName = 'accounting-status-summary.pdf';

    return $pdf->stream($pdfName);
  }

  public function accountingStatusSummaryExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $selectedStatus = $this->resolveAccountingStatus($request);
    $data = $this->buildAccountingStatusSummaryData($startDate, $endDate, $selectedStatus);

    return Excel::download(
      new AccountingStatusSummaryExport($data),
      'Accounting Status Summary.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function dailyOrderStatusSummary(Request $request)
  {
    [$startDate, $endDate] = $this->resolveDailyOrderStatusSummaryDateRange($request);
    $data = $this->buildDailyOrderStatusSummaryData($startDate, $endDate);

    return Inertia::render('Report/DailyOrderStatusSummary', [
      'dailySummary' => $data['dailySummary'],
      'totals' => $data['totals'],
      'orderLists' => $data['orderLists'],
      'startDate' => $data['startDate'],
      'endDate' => $data['endDate'],
    ]);
  }

  public function dailyOrderStatusSummaryPdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveDailyOrderStatusSummaryDateRange($request);
    $data = $this->buildDailyOrderStatusSummaryData($startDate, $endDate);
    $pdf = Pdf::loadView('pdf.daily-order-status-summary', $data)->setPaper('A4', 'landscape');
    $pdfName = 'daily-order-status-summary.pdf';

    return $pdf->stream($pdfName);
  }

  public function dailyOrderStatusSummaryExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveDailyOrderStatusSummaryDateRange($request);
    $data = $this->buildDailyOrderStatusSummaryData($startDate, $endDate);

    return Excel::download(
      new DailyOrderStatusSummaryExport($data),
      'Daily Order Status Summary.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function overdueStageOrders(Request $request, OverdueStageOrdersReportService $reportService)
  {
    return Inertia::render('Report/OverdueStageOrders', $reportService->build($request->all()));
  }

  public function overdueStageOrdersPdf(Request $request, OverdueStageOrdersReportService $reportService)
  {
    $data = $reportService->build($request->all());
    $pdf = Pdf::loadView('pdf.overdue-stage-orders', $data)->setPaper('A4', 'landscape');

    return $pdf->stream('overdue-stage-orders.pdf');
  }

  public function overdueStageOrdersExcel(Request $request, OverdueStageOrdersReportService $reportService)
  {
    $data = $reportService->build($request->all());

    return Excel::download(
      new OverdueStageOrdersExport($data),
      'Overdue Stage Orders.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function marketingReport(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildMarketingReportData($startDate, $endDate);

    return Inertia::render('Report/MarketingReport', $data);
  }

  public function marketingReportPdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildMarketingReportData($startDate, $endDate);
    $pdf = Pdf::loadView('pdf.marketing-report', $data)->setPaper('A4', 'landscape');
    $pdfName = 'marketing-report.pdf';

    return $pdf->stream($pdfName);
  }

  public function marketingReportExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildMarketingReportData($startDate, $endDate);

    return Excel::download(
      new MarketingReportExport($data),
      'Marketing Report.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function salesAppointmentsBySeller(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildSalesAppointmentsBySellerData($startDate, $endDate);

    return Inertia::render('Report/SalesAppointmentsBySeller', $data);
  }

  public function salesAppointmentsBySellerPdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildSalesAppointmentsBySellerData($startDate, $endDate);
    $pdf = Pdf::loadView('pdf.sales-appointments-by-seller', $data)->setPaper('A4', 'portrait');
    $pdfName = 'sales-assigned-orders-and-appointments-by-seller.pdf';

    return $pdf->stream($pdfName);
  }

  public function salesAppointmentsBySellerExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildSalesAppointmentsBySellerData($startDate, $endDate);

    return Excel::download(
      new SalesAppointmentsBySellerExport($data),
      'Sales Assigned Orders and Assigned With Appointment by Seller.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function installerConfirmedSummary(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildInstallerConfirmedSummaryData($startDate, $endDate);

    return Inertia::render('Report/InstallerConfirmedSummary', $data);
  }

  public function ownerAssignedSummary(Request $request)
  {
    [$startDate, $endDate] = $this->resolveOwnerAssignedSummaryDateRange($request);
    $data = $this->buildOwnerAssignedSummaryData($startDate, $endDate);

    return Inertia::render('Report/OwnerAssignedSummary', $data);
  }

  public function supervisorAssignedSummary(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildSupervisorAssignedSummaryData($startDate, $endDate);

    return Inertia::render('Report/SupervisorAssignedSummary', $data);
  }

  public function replannedOrdersSummary(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildReplannedOrdersSummaryData($startDate, $endDate);

    return Inertia::render('Report/ReplannedOrdersSummary', $data);
  }

  public function installerConfirmedSummaryPdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildInstallerConfirmedSummaryData($startDate, $endDate);
    $pdf = Pdf::loadView('pdf.installer-confirmed-summary', $data)->setPaper('A4', 'landscape');
    $pdfName = 'installer-confirmed-summary.pdf';

    return $pdf->stream($pdfName);
  }

  public function installerConfirmedSummaryExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildInstallerConfirmedSummaryData($startDate, $endDate);

    return Excel::download(
      new InstallerConfirmedSummaryExport($data),
      'Installer Confirmed Summary.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function ownerAssignedSummaryPdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveOwnerAssignedSummaryDateRange($request);
    $data = $this->buildOwnerAssignedSummaryData($startDate, $endDate);
    $pdf = Pdf::loadView('pdf.owner-assigned-summary', $data)->setPaper('A4', 'landscape');
    $pdfName = 'owner-report.pdf';

    return $pdf->stream($pdfName);
  }

  public function ownerAssignedSummaryExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveOwnerAssignedSummaryDateRange($request);
    $data = $this->buildOwnerAssignedSummaryData($startDate, $endDate);

    return Excel::download(
      new OwnerAssignedSummaryExport($data),
      'Owner Report.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function supervisorAssignedSummaryPdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildSupervisorAssignedSummaryData($startDate, $endDate);
    $pdf = Pdf::loadView('pdf.supervisor-assigned-summary', $data)->setPaper('A4', 'landscape');
    $pdfName = 'supervisor-assigned-summary.pdf';

    return $pdf->stream($pdfName);
  }

  public function supervisorAssignedSummaryExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildSupervisorAssignedSummaryData($startDate, $endDate);

    return Excel::download(
      new SupervisorAssignedSummaryExport($data),
      'Supervisor Assigned Summary.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  public function replannedOrdersSummaryPdf(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildReplannedOrdersSummaryData($startDate, $endDate);
    $pdf = Pdf::loadView('pdf.replanned-orders-summary', $data)->setPaper('A4', 'landscape');
    $pdfName = 'replanned-orders-summary.pdf';

    return $pdf->stream($pdfName);
  }

  public function replannedOrdersSummaryExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveSummaryDateRange($request);
    $data = $this->buildReplannedOrdersSummaryData($startDate, $endDate);

    return Excel::download(
      new ReplannedOrdersSummaryExport($data),
      'Replanned Orders Summary.xlsx',
      \Maatwebsite\Excel\Excel::XLSX
    );
  }

  private function resolveSummaryDateRange(Request $request): array
  {
    $startDate = $request->start_date
      ? Carbon::parse($request->start_date)->startOfDay()
      : Carbon::now()->startOfMonth();
    $endDate = $request->end_date
      ? Carbon::parse($request->end_date)->endOfDay()
      : Carbon::now()->endOfMonth();

    return [$startDate, $endDate];
  }

  private function resolvePlannedToCompleteBusinessType(Request $request): string
  {
    $allowedTypes = ['all', 'residential', 'commercial'];

    return $request->filled('business_type') && in_array($request->business_type, $allowedTypes, true)
      ? $request->business_type
      : 'all';
  }

  private function resolvePlannedToCompleteTransitionType(Request $request): string
  {
    $allowedTypes = ['planned_completed', 'confirmed_completed'];

    return $request->filled('transition_type') && in_array($request->transition_type, $allowedTypes, true)
      ? $request->transition_type
      : 'planned_completed';
  }

  private function resolvePlannedToCompleteServiceType(Request $request): string
  {
    $allowedServices = [
      'all',
      'SUPPLY',
      ServiceEnum::PICKUP->value,
      ServiceEnum::SERVICE->value,
      ServiceEnum::INSTALLATION->value,
      ServiceEnum::DELIVERY->value,
    ];

    return $request->filled('service') && in_array($request->service, $allowedServices, true)
      ? $request->service
      : 'all';
  }

  private function buildPlannedToCompleteAverageData(
    Carbon $startDate,
    Carbon $endDate,
    string $businessType,
    string $transitionType,
    string $serviceType
  ): array
  {
    $startStatus = $this->resolveTransitionStartStatus($transitionType);
    $transitionLabel = $this->resolveTransitionLabel($transitionType);

    $startStatusDates = OrderStatus::query()
      ->select('order_id', DB::raw('MIN(created_at) as started_at'))
      ->where('status', $startStatus)
      ->groupBy('order_id');

    $completedStatus = OrderStatus::query()
      ->select('order_id', DB::raw('MIN(created_at) as completed_at'))
      ->where('status', OrderStatusEnum::COMPLETE->value)
      ->groupBy('order_id');

    $rowsQuery = DB::table('orders')
      ->joinSub($startStatusDates, 'start_status', function ($join) {
        $join->on('start_status.order_id', '=', 'orders.id');
      })
      ->joinSub($completedStatus, 'completed_status', function ($join) {
        $join->on('completed_status.order_id', '=', 'orders.id');
      })
      ->leftJoin('types_of_housing', 'types_of_housing.id', '=', 'orders.type_of_housing_id')
      ->whereNull('orders.deleted_at')
      ->whereColumn('completed_status.completed_at', '>=', 'start_status.started_at')
      ->whereBetween('completed_status.completed_at', [$startDate, $endDate])
      ->select(
        'orders.id',
        'orders.order_number',
        'orders.name',
        'orders.service',
        'orders.order_type',
        'types_of_housing.name as type_of_housing',
        'start_status.started_at',
        'completed_status.completed_at'
      )
      ->orderByDesc('completed_status.completed_at');

    if ($businessType === 'commercial') {
      $rowsQuery->where(function ($query) {
        $query->where('orders.order_type', OrderTypeEnum::COMMERCIAL->value)
          ->orWhereRaw('LOWER(types_of_housing.name) = ?', ['commercial']);
      });
    } elseif ($businessType === 'residential') {
      $rowsQuery->where(function ($query) {
        $query->where('orders.order_type', OrderTypeEnum::RESIDENTIAL->value)
          ->orWhereRaw('LOWER(types_of_housing.name) IN (?, ?)', ['apartment', 'single family home']);
      });
    }

    if ($serviceType === 'SUPPLY') {
      $rowsQuery->whereIn('orders.service', [
        ServiceEnum::PICKUP->value,
        ServiceEnum::DELIVERY->value,
      ]);
    } elseif ($serviceType !== 'all') {
      $rowsQuery->where('orders.service', $serviceType);
    }

    $rows = $rowsQuery->get()->map(function ($row) {
      $startedAt = Carbon::parse($row->started_at);
      $completedAt = Carbon::parse($row->completed_at);
      $durationSeconds = $startedAt->diffInSeconds($completedAt);

      return [
        'id' => $row->id,
        'order_number' => $row->order_number,
        'name' => $row->name,
        'service' => $row->service,
        'order_type' => $row->order_type,
        'type_of_housing' => $row->type_of_housing,
        'start_at' => $startedAt->toDateTimeString(),
        'completed_at' => $completedAt->toDateTimeString(),
        'duration_seconds' => $durationSeconds,
        'duration_days' => round($durationSeconds / 86400, 2),
        'duration_label' => $this->formatDurationSeconds($durationSeconds),
      ];
    })->values();

    $totalOrders = $rows->count();
    $averageDurationSeconds = $totalOrders > 0
      ? (int) round($rows->avg('duration_seconds'))
      : 0;

    return [
      'rows' => $rows,
      'totalOrders' => $totalOrders,
      'averageDurationSeconds' => $averageDurationSeconds,
      'averageDurationDays' => $totalOrders > 0 ? round($averageDurationSeconds / 86400, 2) : 0,
      'averageDurationLabel' => $this->formatDurationSeconds($averageDurationSeconds),
      'transitionType' => $transitionType,
      'transitionLabel' => $transitionLabel,
      'startStatusLabel' => $transitionType === 'confirmed_completed' ? 'Confirmed At' : 'Planned At',
      'businessType' => $businessType,
      'businessTypeLabel' => $this->resolveBusinessTypeLabel($businessType),
      'serviceType' => $serviceType,
      'serviceTypeLabel' => $serviceType === 'all' ? 'ALL SERVICES' : $serviceType,
      'serviceOptions' => [
        ['label' => 'ALL', 'value' => 'all'],
        ['label' => 'SUPPLY', 'value' => 'SUPPLY'],
        ['label' => ServiceEnum::PICKUP->value, 'value' => ServiceEnum::PICKUP->value],
        ['label' => ServiceEnum::SERVICE->value, 'value' => ServiceEnum::SERVICE->value],
        ['label' => ServiceEnum::INSTALLATION->value, 'value' => ServiceEnum::INSTALLATION->value],
        ['label' => ServiceEnum::DELIVERY->value, 'value' => ServiceEnum::DELIVERY->value],
      ],
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function resolveTransitionStartStatus(string $transitionType): string
  {
    return $transitionType === 'confirmed_completed'
      ? OrderStatusEnum::CONFIRMED->value
      : OrderStatusEnum::PLANNED->value;
  }

  private function resolveTransitionLabel(string $transitionType): string
  {
    return $transitionType === 'confirmed_completed'
      ? 'CONFIRMED -> COMPLETE'
      : 'PLANNED -> COMPLETE';
  }

  private function resolveBusinessTypeLabel(string $businessType): string
  {
    return match ($businessType) {
      'commercial' => 'COMMERCIAL',
      'residential' => 'RESIDENTIAL',
      default => 'ALL TYPES',
    };
  }

  private function formatDurationSeconds(int $seconds): string
  {
    if ($seconds <= 0) {
      return '0 minutes';
    }

    $days = intdiv($seconds, 86400);
    $remaining = $seconds % 86400;
    $hours = intdiv($remaining, 3600);
    $remaining = $remaining % 3600;
    $minutes = intdiv($remaining, 60);

    $parts = [];

    if ($days > 0) {
      $parts[] = $days . ' day' . ($days === 1 ? '' : 's');
    }

    if ($hours > 0) {
      $parts[] = $hours . ' hour' . ($hours === 1 ? '' : 's');
    }

    if ($minutes > 0) {
      $parts[] = $minutes . ' minute' . ($minutes === 1 ? '' : 's');
    }

    return empty($parts) ? 'less than 1 minute' : implode(' ', $parts);
  }

  private function resolveUniqueOrderIdsByStatus(string $status, Carbon $startDate, Carbon $endDate): Collection
  {
    return OrderStatus::query()
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->whereNull('orders.deleted_at')
      ->where('order_status.status', $status)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->distinct()
      ->pluck('order_status.order_id');
  }

  private function resolveOwnerAssignedSummaryDateRange(Request $request): array
  {
    $firstOrderCreatedAt = Order::query()
      ->whereNull('deleted_at')
      ->min('created_at');

    $startDate = $request->start_date
      ? Carbon::parse($request->start_date)->startOfDay()
      : ($firstOrderCreatedAt
        ? Carbon::parse($firstOrderCreatedAt)->startOfDay()
        : Carbon::now()->startOfMonth());

    $endDate = $request->end_date
      ? Carbon::parse($request->end_date)->endOfDay()
      : Carbon::now()->endOfDay();

    return [$startDate, $endDate];
  }

  private function resolveDailyOrderStatusSummaryDateRange(Request $request): array
  {
    $startDate = $request->start_date
      ? Carbon::parse($request->start_date)->startOfDay()
      : Carbon::today()->startOfDay();
    $endDate = $request->end_date
      ? Carbon::parse($request->end_date)->endOfDay()
      : Carbon::today()->endOfDay();

    return [$startDate, $endDate];
  }

  private function buildDailyOrderStatusSummaryData(Carbon $startDate, Carbon $endDate): array
  {
    $totalEligibleStatuses = [
      OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
      OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
      OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
      OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
      OrderStatusEnum::QUALIFIED->value,
    ];

    $createdOrdersBase = Order::query()
      ->whereNull('orders.deleted_at')
      ->whereBetween('orders.created_at', [$startDate, $endDate])
      ->where(function ($query) {
        $query->whereNull('orders.order_type')
          ->orWhere('orders.order_type', '!=', OrderTypeEnum::COMMERCIAL->value);
      });

    $totalEligibleOrdersBase = (clone $createdOrdersBase)
      ->whereExists(function ($query) use ($totalEligibleStatuses) {
        $query->select(DB::raw(1))
          ->from('order_status')
          ->whereColumn('order_status.order_id', 'orders.id')
          ->whereIn('order_status.status', $totalEligibleStatuses);
      });

    $cohort = (clone $totalEligibleOrdersBase)
      ->selectRaw('DATE(orders.created_at) as summary_date, orders.id as order_id')
      ->distinct();

    $cohortWithSchedule = (clone $totalEligibleOrdersBase)
      ->whereNotNull('orders.schedule_appointment')
      ->selectRaw('DATE(orders.created_at) as summary_date, orders.id as order_id')
      ->distinct();

    $cohortCounts = DB::query()
      ->fromSub($cohort, 'cohort')
      ->selectRaw('summary_date, COUNT(DISTINCT order_id) as total')
      ->groupBy('summary_date')
      ->orderBy('summary_date')
      ->get()
      ->keyBy('summary_date');

    $qualifiedBase = OrderStatus::query()
      ->select('order_status.order_id')
      ->where('order_status.status', OrderStatusEnum::QUALIFIED->value)
      ->distinct();

    $qualifiedCounts = DB::query()
      ->fromSub($cohort, 'cohort')
      ->joinSub($qualifiedBase, 'qualified', function ($join) {
        $join->on('qualified.order_id', '=', 'cohort.order_id');
      })
      ->selectRaw('cohort.summary_date as summary_date, COUNT(DISTINCT cohort.order_id) as total')
      ->groupBy('cohort.summary_date')
      ->orderBy('cohort.summary_date')
      ->get()
      ->keyBy('summary_date');

    $estimateBase = OrderStatus::query()
      ->select('order_status.order_id')
      ->where('order_status.status', OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value)
      ->distinct();

    $estimateCounts = DB::query()
      ->fromSub($cohortWithSchedule, 'cohort_schedule')
      ->joinSub($estimateBase, 'estimate', function ($join) {
        $join->on('estimate.order_id', '=', 'cohort_schedule.order_id');
      })
      ->selectRaw('cohort_schedule.summary_date as summary_date, COUNT(DISTINCT cohort_schedule.order_id) as total')
      ->groupBy('cohort_schedule.summary_date')
      ->orderBy('cohort_schedule.summary_date')
      ->get()
      ->keyBy('summary_date');

    $estimateStatusDateTransitions = DB::table('order_status')
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->whereNull('orders.deleted_at')
      ->where('order_status.status', OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->whereNotNull('orders.schedule_appointment')
      ->where(function ($query) {
        $query->whereNull('orders.order_type')
          ->orWhere('orders.order_type', '!=', OrderTypeEnum::COMMERCIAL->value);
      })
      ->selectRaw('order_status.order_id, MAX(order_status.created_at) as estimate_appt_schedule_at')
      ->groupBy('order_status.order_id');

    $estimateStatusDateCounts = DB::query()
      ->fromSub($estimateStatusDateTransitions, 'estimate_status_date_transitions')
      ->selectRaw('DATE(estimate_appt_schedule_at) as summary_date, COUNT(DISTINCT order_id) as total')
      ->groupBy('summary_date')
      ->orderBy('summary_date')
      ->get()
      ->keyBy('summary_date');

    $qualifiedStatusDateTransitions = DB::table('order_status')
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->whereNull('orders.deleted_at')
      ->where('order_status.status', OrderStatusEnum::QUALIFIED->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->where(function ($query) {
        $query->whereNull('orders.order_type')
          ->orWhere('orders.order_type', '!=', OrderTypeEnum::COMMERCIAL->value);
      })
      ->selectRaw('order_status.order_id, MAX(order_status.created_at) as qualified_at')
      ->groupBy('order_status.order_id');

    $qualifiedStatusDateCounts = DB::query()
      ->fromSub($qualifiedStatusDateTransitions, 'qualified_status_date_transitions')
      ->selectRaw('DATE(qualified_at) as summary_date, COUNT(DISTINCT order_id) as total')
      ->groupBy('summary_date')
      ->orderBy('summary_date')
      ->get()
      ->keyBy('summary_date');

    $lostRequestTransitions = DB::table('order_status')
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->whereNull('orders.deleted_at')
      ->where('order_status.status', OrderStatusEnum::LOST_CUSTOMER_REQUEST->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->where(function ($query) {
        $query->whereNull('orders.order_type')
          ->orWhere('orders.order_type', '!=', OrderTypeEnum::COMMERCIAL->value);
      })
      ->selectRaw('order_status.order_id, MAX(order_status.created_at) as lost_request_at')
      ->groupBy('order_status.order_id');

    $lostRequestCounts = DB::query()
      ->fromSub($lostRequestTransitions, 'lost_request_transitions')
      ->selectRaw('DATE(lost_request_at) as summary_date, COUNT(DISTINCT order_id) as total')
      ->groupBy('summary_date')
      ->orderBy('summary_date')
      ->get()
      ->keyBy('summary_date');

    $totalOrderIds = DB::query()
      ->fromSub($cohort, 'cohort')
      ->select('cohort.order_id')
      ->distinct()
      ->pluck('cohort.order_id');

    $qualifiedOrderIds = DB::query()
      ->fromSub($cohort, 'cohort')
      ->joinSub($qualifiedBase, 'qualified', function ($join) {
        $join->on('qualified.order_id', '=', 'cohort.order_id');
      })
      ->select('cohort.order_id')
      ->distinct()
      ->pluck('cohort.order_id');

    $estimateOrderIds = DB::query()
      ->fromSub($cohortWithSchedule, 'cohort_schedule')
      ->joinSub($estimateBase, 'estimate', function ($join) {
        $join->on('estimate.order_id', '=', 'cohort_schedule.order_id');
      })
      ->select('cohort_schedule.order_id')
      ->distinct()
      ->pluck('cohort_schedule.order_id');

    $estimateStatusDateRows = DB::query()
      ->fromSub($estimateStatusDateTransitions, 'estimate_status_date_transitions')
      ->select('order_id', 'estimate_appt_schedule_at')
      ->orderBy('estimate_appt_schedule_at')
      ->orderBy('order_id')
      ->get();

    $estimateStatusDateOrderIds = $estimateStatusDateRows->pluck('order_id');
    $estimateStatusDateMetadata = $estimateStatusDateRows
      ->keyBy(static fn ($row) => (int) $row->order_id)
      ->map(static function ($row) {
        return [
          'status_date' => $row->estimate_appt_schedule_at
            ? Carbon::parse($row->estimate_appt_schedule_at)->toDateString()
            : null,
        ];
      })
      ->all();

    $qualifiedStatusDateRows = DB::query()
      ->fromSub($qualifiedStatusDateTransitions, 'qualified_status_date_transitions')
      ->select('order_id', 'qualified_at')
      ->orderBy('qualified_at')
      ->orderBy('order_id')
      ->get();

    $qualifiedStatusDateOrderIds = $qualifiedStatusDateRows->pluck('order_id');
    $qualifiedStatusDateMetadata = $qualifiedStatusDateRows
      ->keyBy(static fn ($row) => (int) $row->order_id)
      ->map(static function ($row) {
        return [
          'status_date' => $row->qualified_at
            ? Carbon::parse($row->qualified_at)->toDateString()
            : null,
        ];
      })
      ->all();

    $lostRequestRows = DB::query()
      ->fromSub($lostRequestTransitions, 'lost_request_transitions')
      ->select('order_id', 'lost_request_at')
      ->orderBy('lost_request_at')
      ->orderBy('order_id')
      ->get();

    $lostRequestOrderIds = $lostRequestRows->pluck('order_id');
    $lostRequestMetadata = $this->buildLostRequestMetadata($lostRequestOrderIds, $lostRequestRows);

    $daily = [];
    $current = $startDate->copy()->startOfDay();

    while ($current->lte($endDate)) {
      $dateKey = $current->toDateString();
      $daily[$dateKey] = [
        'date' => $dateKey,
        'qualified' => 0,
        'qualified_by_status_date' => 0,
        'estimate_appt_schedule' => 0,
        'estimate_appt_schedule_by_status_date' => 0,
        'lost_request' => 0,
        'total' => 0,
      ];
      $current->addDay();
    }

    foreach ($qualifiedCounts as $dateKey => $row) {
      if (!isset($daily[$dateKey])) {
        continue;
      }

      $daily[$dateKey]['qualified'] = (int) $row->total;
    }

    foreach ($qualifiedStatusDateCounts as $dateKey => $row) {
      if (!isset($daily[$dateKey])) {
        continue;
      }

      $daily[$dateKey]['qualified_by_status_date'] = (int) $row->total;
    }

    foreach ($cohortCounts as $dateKey => $row) {
      if (!isset($daily[$dateKey])) {
        continue;
      }

      $daily[$dateKey]['total'] = (int) $row->total;
    }

    foreach ($estimateCounts as $dateKey => $row) {
      if (!isset($daily[$dateKey])) {
        continue;
      }

      $daily[$dateKey]['estimate_appt_schedule'] = (int) $row->total;
    }

    foreach ($estimateStatusDateCounts as $dateKey => $row) {
      if (!isset($daily[$dateKey])) {
        continue;
      }

      $daily[$dateKey]['estimate_appt_schedule_by_status_date'] = (int) $row->total;
    }

    foreach ($lostRequestCounts as $dateKey => $row) {
      if (!isset($daily[$dateKey])) {
        continue;
      }

      $daily[$dateKey]['lost_request'] = (int) $row->total;
    }

    $dailySummary = collect($daily)->values()->map(function ($row) {
      return [
        'date' => $row['date'],
        'new_request_qualified' => $row['total'],
        'qualified' => $row['qualified'],
        'qualified_by_status_date' => $row['qualified_by_status_date'],
        'estimate_appt_schedule' => $row['estimate_appt_schedule'],
        'estimate_appt_schedule_by_status_date' => $row['estimate_appt_schedule_by_status_date'],
        'lost_request' => $row['lost_request'],
      ];
    });

    $totals = [
      'total' => $dailySummary->sum('new_request_qualified'),
      'qualified' => $dailySummary->sum('qualified'),
      'qualified_by_status_date' => $qualifiedStatusDateRows->count(),
      'estimate_appt_schedule' => $dailySummary->sum('estimate_appt_schedule'),
      'estimate_appt_schedule_by_status_date' => $estimateStatusDateRows->count(),
      'lost_request' => $dailySummary->sum('lost_request'),
    ];

    $orderLists = [
      'total' => $this->buildOrderReferenceList($totalOrderIds)->values(),
      'qualified' => $this->buildOrderReferenceList($qualifiedOrderIds)->values(),
      'qualified_by_status_date' => $this->buildOrderReferenceList($qualifiedStatusDateOrderIds, $qualifiedStatusDateMetadata)
        ->sortBy(static fn (array $order) => sprintf(
          '%s-%010d',
          $order['status_date'] ?? '9999-12-31',
          (int) $order['id']
        ))
        ->values(),
      'estimate_appt_schedule' => $this->buildOrderReferenceList($estimateOrderIds)->values(),
      'estimate_appt_schedule_by_status_date' => $this->buildOrderReferenceList($estimateStatusDateOrderIds, $estimateStatusDateMetadata)
        ->sortBy(static fn (array $order) => sprintf(
          '%s-%010d',
          $order['status_date'] ?? '9999-12-31',
          (int) $order['id']
        ))
        ->values(),
      'lost_request' => $this->buildOrderReferenceList($lostRequestOrderIds, $lostRequestMetadata)
        ->sortBy(static fn (array $order) => sprintf(
          '%s-%010d',
          $order['status_date'] ?? '9999-12-31',
          (int) $order['id']
        ))
        ->values(),
    ];

    $totals['total_orders'] = $orderLists['total']->count();

    return [
      'dailySummary' => $dailySummary,
      'totals' => $totals,
      'orderLists' => $orderLists,
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function buildOrderReferenceList(Collection $orderIds, array $metadataByOrderId = []): Collection
  {
    $uniqueOrderIds = $orderIds
      ->map(static fn ($orderId) => (int) $orderId)
      ->filter(static fn (int $orderId) => $orderId > 0)
      ->unique()
      ->values();

    if ($uniqueOrderIds->isEmpty()) {
      return collect();
    }

    return Order::query()
      ->whereIn('id', $uniqueOrderIds)
      ->orderBy('created_at')
      ->orderBy('id')
      ->get(['id', 'name', 'status', 'created_at'])
      ->map(static function (Order $order) use ($metadataByOrderId) {
        $metadata = $metadataByOrderId[$order->id] ?? [];
        $orderName = trim((string) ($order->name ?? ''));
        $createdDate = $order->created_at ? $order->created_at->toDateString() : null;
        $currentStatus = trim((string) ($order->status ?? ''));
        $statusLabel = $currentStatus !== '' ? $currentStatus : '-';

        return array_merge([
          'id' => $order->id,
          'name' => $orderName,
          'created_date' => $createdDate,
          'current_status' => $statusLabel,
          'label' => $orderName !== ''
            ? '#' . $order->id . ' - ' . $orderName
            : '#' . $order->id,
        ], $metadata);
      })
      ->values();
  }

  private function buildLostRequestMetadata(Collection $orderIds, Collection $lostRequestRows): array
  {
    $uniqueOrderIds = $orderIds
      ->map(static fn ($orderId) => (int) $orderId)
      ->filter(static fn (int $orderId) => $orderId > 0)
      ->unique()
      ->values();

    if ($uniqueOrderIds->isEmpty()) {
      return [];
    }

    $lossReasonsByOrderId = Order::query()
      ->whereIn('id', $uniqueOrderIds)
      ->pluck('loss_reason_frontdesk', 'id')
      ->map(static fn ($reason) => filled($reason) ? trim((string) $reason) : null)
      ->all();

    return $lostRequestRows
      ->keyBy(static fn ($row) => (int) $row->order_id)
      ->map(static function ($row) use ($lossReasonsByOrderId) {
        $orderId = (int) $row->order_id;

        return [
          'status_date' => $row->lost_request_at
            ? Carbon::parse($row->lost_request_at)->toDateString()
            : null,
          'loss_reason_frontdesk' => $lossReasonsByOrderId[$orderId] ?? null,
        ];
      })
      ->all();
  }

  private function resolveAccountingStatus(Request $request): ?string
  {
    $allowedStatuses = [
      OrderStatusEnum::ACCOUNT_RECEIPT->value,
      OrderStatusEnum::COMPLETE->value,
    ];

    return $request->filled('status') && in_array($request->status, $allowedStatuses, true)
      ? $request->status
      : null;
  }

  private function buildAccountingStatusSummaryData(Carbon $startDate, Carbon $endDate, ?string $selectedStatus = null): array
  {
    $availableStatuses = [
      OrderStatusEnum::ACCOUNT_RECEIPT->value,
      OrderStatusEnum::COMPLETE->value,
    ];
    $statuses = $selectedStatus ? [$selectedStatus] : $availableStatuses;

    $rows = OrderStatus::query()
      ->with([
        'order:id,name,project_amount',
        'order.owners:id,name',
      ])
      ->whereIn('status', $statuses)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->whereHas('order')
      ->orderByDesc('created_at')
      ->get(['id', 'order_id', 'status', 'created_at'])
      ->map(function (OrderStatus $statusRow) {
        $owners = $statusRow->order?->owners
          ? $statusRow->order->owners->pluck('name')->filter()->implode(', ')
          : '';

        return [
          'id' => $statusRow->id,
          'status' => $statusRow->status,
          'order_name' => $statusRow->order?->name,
          'owner' => $owners,
          'amount' => $statusRow->order?->project_amount,
          'status_date' => $statusRow->created_at?->toDateTimeString(),
        ];
      })
      ->values();

    return [
      'rows' => $rows,
      'totals' => [
        'total' => $rows->count(),
        'account_receipt' => $rows->where('status', OrderStatusEnum::ACCOUNT_RECEIPT->value)->count(),
        'complete' => $rows->where('status', OrderStatusEnum::COMPLETE->value)->count(),
      ],
      'selectedStatus' => $selectedStatus,
      'availableStatuses' => $availableStatuses,
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function buildMarketingReportData(Carbon $startDate, Carbon $endDate): array
  {
    $sources = [
      ContactSourceEnum::GOOGLE_ADS->value,
      ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
      'GOOGLE_ADS',
      'INSTAGRAM_FACEBOOK',
    ];
    $sources = array_values(array_unique($sources));

    $sourceGroups = [
      ContactSourceEnum::INSTAGRAM_FACEBOOK->value => [
        ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
        'INSTAGRAM_FACEBOOK',
      ],
      ContactSourceEnum::GOOGLE_ADS->value => [
        ContactSourceEnum::GOOGLE_ADS->value,
        'GOOGLE_ADS',
      ],
    ];

    $lossReasons = [
      LostReasonfrontdeskEnum::DEALER->value,
      LostReasonfrontdeskEnum::STOCK->value,
    ];

    $totalClientsInRange = Client::query()
      ->whereBetween('created_at', [$startDate, $endDate])
      ->whereIn('source', $sources)
      ->count();

    $qualifiedStatus = DB::table('order_status')
      ->select(
        'order_id',
        DB::raw('MIN(created_at) as first_qualified_at'),
        DB::raw('MAX(created_at) as last_qualified_at')
      )
      ->where('status', OrderStatusEnum::QUALIFIED->value)
      ->groupBy('order_id');

    $qualifiedOrdersBase = DB::table('orders')
      ->leftJoinSub($qualifiedStatus, 'qualified_status', function ($join) {
        $join->on('qualified_status.order_id', '=', 'orders.id');
      })
      ->whereNull('orders.deleted_at')
      ->where(function ($query) {
        $query->whereNotNull('qualified_status.order_id')
          ->orWhere('orders.status', OrderStatusEnum::QUALIFIED->value);
      })
      ->select(
        'orders.id as order_id',
        DB::raw('COALESCE(qualified_status.first_qualified_at, orders.created_at) as first_qualified_at'),
        DB::raw('COALESCE(qualified_status.last_qualified_at, orders.created_at) as last_qualified_at')
      );

    $qualifiedDirect = DB::table('orders')
      ->joinSub($qualifiedOrdersBase, 'qualified_orders', function ($join) {
        $join->on('qualified_orders.order_id', '=', 'orders.id');
      })
      ->whereNotNull('orders.client_id')
      ->select(
        'orders.client_id',
        'qualified_orders.order_id',
        'qualified_orders.first_qualified_at',
        'qualified_orders.last_qualified_at'
      );

    $qualifiedViaContact = DB::table('order_company_contacts as occ')
      ->joinSub($qualifiedOrdersBase, 'qualified_orders', function ($join) {
        $join->on('qualified_orders.order_id', '=', 'occ.order_id');
      })
      ->whereNull('occ.deleted_at')
      ->whereNotNull('occ.client_id')
      ->select(
        'occ.client_id',
        'qualified_orders.order_id',
        'qualified_orders.first_qualified_at',
        'qualified_orders.last_qualified_at'
      );

    $qualifiedClientOrders = $qualifiedDirect->union($qualifiedViaContact);

    $qualifiedClientsBySource = [];
    foreach ($sourceGroups as $label => $groupSources) {
      $qualifiedClientsBySource[$label] = DB::query()
        ->fromSub($qualifiedClientOrders, 'qualified_client_orders')
        ->join('clients', 'clients.id', '=', 'qualified_client_orders.client_id')
        ->whereBetween('clients.created_at', [$startDate, $endDate])
        ->whereIn('clients.source', $groupSources)
        ->distinct('qualified_client_orders.client_id')
        ->count('qualified_client_orders.client_id');
    }

    $qualifiedByClient = DB::query()
      ->fromSub($qualifiedClientOrders, 'qualified_client_orders')
      ->select(
        'client_id',
        DB::raw('COUNT(DISTINCT order_id) as qualified_orders_count'),
        DB::raw('MIN(first_qualified_at) as first_qualified_at'),
        DB::raw('MAX(last_qualified_at) as last_qualified_at')
      )
      ->groupBy('client_id');

    $qualifiedClients = Client::query()
      ->joinSub($qualifiedByClient, 'qualified_clients', function ($join) {
        $join->on('qualified_clients.client_id', '=', 'clients.id');
      })
      ->whereBetween('clients.created_at', [$startDate, $endDate])
      ->whereIn('clients.source', $sources)
      ->orderBy('clients.created_at')
      ->get([
        'clients.id',
        'clients.name',
        'clients.phone',
        'clients.email',
        'clients.source',
        'clients.created_at',
        'qualified_clients.qualified_orders_count',
        'qualified_clients.first_qualified_at',
        'qualified_clients.last_qualified_at',
      ])
      ->map(function ($row) {
        return [
          'id' => $row->id,
          'name' => $row->name,
          'phone' => $row->phone,
          'email' => $row->email,
          'source' => $row->source,
          'created_at' => $row->created_at ? Carbon::parse($row->created_at)->toDateString() : null,
          'qualified_orders_count' => (int) $row->qualified_orders_count,
          'first_qualified_at' => $row->first_qualified_at ? Carbon::parse($row->first_qualified_at)->toDateString() : null,
          'last_qualified_at' => $row->last_qualified_at ? Carbon::parse($row->last_qualified_at)->toDateString() : null,
        ];
      })
      ->values();

    $qualifiedWithAppointmentByClient = DB::query()
      ->fromSub($qualifiedClientOrders, 'qualified_client_orders')
      ->join('orders', 'orders.id', '=', 'qualified_client_orders.order_id')
      ->join('clients', 'clients.id', '=', 'qualified_client_orders.client_id')
      ->whereBetween('clients.created_at', [$startDate, $endDate])
      ->whereIn('clients.source', $sources)
      ->whereNotNull('orders.schedule_appointment')
      ->select(
        'qualified_client_orders.client_id',
        DB::raw('MIN(orders.schedule_appointment) as first_appointment_at')
      )
      ->groupBy('qualified_client_orders.client_id');

    $qualifiedClientsWithAppointment = Client::query()
      ->joinSub($qualifiedWithAppointmentByClient, 'qualified_with_appointment', function ($join) {
        $join->on('qualified_with_appointment.client_id', '=', 'clients.id');
      })
      ->orderBy('qualified_with_appointment.first_appointment_at')
      ->get([
        'clients.id',
        'clients.name',
        'clients.source',
        'qualified_with_appointment.first_appointment_at',
      ])
      ->map(function ($row) {
        return [
          'id' => $row->id,
          'name' => $row->name,
          'source' => $row->source,
          'appointment_date' => $row->first_appointment_at
            ? Carbon::parse($row->first_appointment_at)->toDateString()
            : null,
        ];
      })
      ->values();

    $lostOrdersBase = DB::table('orders')
      ->whereNull('orders.deleted_at')
      ->where('orders.status', OrderStatusEnum::LOST_CUSTOMER_REQUEST->value)
      ->whereIn('orders.loss_reason_frontdesk', $lossReasons)
      ->select(
        'orders.id as order_id',
        'orders.created_at',
        'orders.loss_reason_frontdesk'
      );

    $lostDirect = DB::table('orders')
      ->joinSub($lostOrdersBase, 'lost_orders', function ($join) {
        $join->on('lost_orders.order_id', '=', 'orders.id');
      })
      ->whereNotNull('orders.client_id')
      ->select(
        'orders.client_id',
        'lost_orders.order_id',
        'lost_orders.created_at',
        'lost_orders.loss_reason_frontdesk'
      );

    $lostViaContact = DB::table('order_company_contacts as occ')
      ->joinSub($lostOrdersBase, 'lost_orders', function ($join) {
        $join->on('lost_orders.order_id', '=', 'occ.order_id');
      })
      ->whereNull('occ.deleted_at')
      ->whereNotNull('occ.client_id')
      ->select(
        'occ.client_id',
        'lost_orders.order_id',
        'lost_orders.created_at',
        'lost_orders.loss_reason_frontdesk'
      );

    $lostClientOrders = $lostDirect->union($lostViaContact);

    $lostClientsByReason = DB::query()
      ->fromSub($lostClientOrders, 'lost_client_orders')
      ->join('clients', 'clients.id', '=', 'lost_client_orders.client_id')
      ->whereBetween('clients.created_at', [$startDate, $endDate])
      ->whereIn('clients.source', $sources)
      ->whereIn('lost_client_orders.loss_reason_frontdesk', $lossReasons)
      ->select('lost_client_orders.loss_reason_frontdesk', DB::raw('COUNT(DISTINCT lost_client_orders.client_id) as total'))
      ->groupBy('lost_client_orders.loss_reason_frontdesk')
      ->get()
      ->pluck('total', 'loss_reason_frontdesk')
      ->all();

    foreach ($lossReasons as $reason) {
      if (!array_key_exists($reason, $lostClientsByReason)) {
        $lostClientsByReason[$reason] = 0;
      }
    }

    $lostByClient = DB::query()
      ->fromSub($lostClientOrders, 'lost_client_orders')
      ->select(
        'client_id',
        DB::raw('COUNT(DISTINCT order_id) as lost_orders_count'),
        DB::raw('MIN(created_at) as first_lost_order_at'),
        DB::raw('MAX(created_at) as last_lost_order_at'),
        DB::raw('GROUP_CONCAT(DISTINCT loss_reason_frontdesk) as loss_reasons')
      )
      ->groupBy('client_id');

    $lostClients = Client::query()
      ->joinSub($lostByClient, 'lost_clients', function ($join) {
        $join->on('lost_clients.client_id', '=', 'clients.id');
      })
      ->whereBetween('clients.created_at', [$startDate, $endDate])
      ->whereIn('clients.source', $sources)
      ->orderBy('clients.created_at')
      ->get([
        'clients.id',
        'clients.name',
        'clients.phone',
        'clients.email',
        'clients.source',
        'clients.created_at',
        'lost_clients.lost_orders_count',
        'lost_clients.loss_reasons',
        'lost_clients.first_lost_order_at',
        'lost_clients.last_lost_order_at',
      ])
      ->map(function ($row) {
        return [
          'id' => $row->id,
          'name' => $row->name,
          'phone' => $row->phone,
          'email' => $row->email,
          'source' => $row->source,
          'created_at' => $row->created_at ? Carbon::parse($row->created_at)->toDateString() : null,
          'lost_orders_count' => (int) $row->lost_orders_count,
          'loss_reasons' => $row->loss_reasons,
          'first_lost_order_at' => $row->first_lost_order_at ? Carbon::parse($row->first_lost_order_at)->toDateString() : null,
          'last_lost_order_at' => $row->last_lost_order_at ? Carbon::parse($row->last_lost_order_at)->toDateString() : null,
        ];
      })
      ->values();

    return [
      'qualifiedClients' => $qualifiedClients,
      'qualifiedClientsWithAppointment' => $qualifiedClientsWithAppointment,
      'lostClients' => $lostClients,
      'totals' => [
        'total_clients' => $totalClientsInRange,
        'qualified_clients' => $qualifiedClients->count(),
        'qualified_clients_with_appointment' => $qualifiedClientsWithAppointment->count(),
        'lost_clients' => $lostClients->count(),
        'qualified_orders' => $qualifiedClients->sum('qualified_orders_count'),
        'lost_orders' => $lostClients->sum('lost_orders_count'),
        'grand_total_clients' => $qualifiedClients->count() + $lostClients->count(),
        'qualified_clients_by_source' => $qualifiedClientsBySource,
        'lost_clients_by_reason' => $lostClientsByReason,
      ],
      'filters' => [
        'sources' => $sources,
        'qualified_status' => OrderStatusEnum::QUALIFIED->value,
        'lost_status' => OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
        'loss_reasons' => $lossReasons,
      ],
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function buildSalesAppointmentsBySellerData(Carbon $startDate, Carbon $endDate): array
  {
    $owners = User::role(RoleEnum::OWNER->value)
      ->where('status', StatusUserEnum::ACTIVE->value)
      ->select('users.id', 'users.name');

    $ownerAssignments = DB::table('owner_user')
      ->selectRaw('user_id, order_id, MIN(created_at) as assigned_at')
      ->whereNull('deleted_at')
      ->groupBy('user_id', 'order_id');

    $qualifiedOrdersInRange = DB::table('order_status')
      ->join('orders', function ($join) {
        $join->on('orders.id', '=', 'order_status.order_id')
          ->whereNull('orders.deleted_at')
          ->where('orders.counts_for_owner_commission', true);
      })
      ->where('order_status.status', OrderStatusEnum::QUALIFIED->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->select('order_status.order_id')
      ->distinct();

    $assignedOrdersExpression = 'COUNT(DISTINCT qualified_orders.order_id)';
    $hasAppointmentExpression = '(orders.schedule_appointment IS NOT NULL OR EXISTS (
      SELECT 1
      FROM client_address
      WHERE client_address.client_id = orders.client_id
        AND client_address.deleted_at IS NULL
        AND client_address.appointment_date IS NOT NULL
    ))';
    $assignedWithAppointmentExpression = 'COUNT(DISTINCT CASE WHEN ' . $hasAppointmentExpression . ' THEN qualified_orders.order_id END)';

    $summary = DB::query()
      ->fromSub($owners, 'owner_users')
      ->leftJoinSub($ownerAssignments, 'owner_assignments', function ($join) {
        $join->on('owner_assignments.user_id', '=', 'owner_users.id');
      })
      ->leftJoinSub($qualifiedOrdersInRange, 'qualified_orders', function ($join) {
        $join->on('qualified_orders.order_id', '=', 'owner_assignments.order_id');
      })
      ->leftJoin('orders', 'orders.id', '=', 'qualified_orders.order_id')
      ->select('owner_users.id as seller_id', 'owner_users.name as seller_name')
      ->selectRaw($assignedOrdersExpression . ' as assigned_orders_count')
      ->selectRaw($assignedWithAppointmentExpression . ' as assigned_with_appointment_count')
      ->groupBy('owner_users.id', 'owner_users.name')
      ->orderBy('owner_users.name')
      ->get();

    $totalAssignedOrders = (int) $summary->sum('assigned_orders_count');
    $totalAssignedWithAppointment = (int) $summary->sum('assigned_with_appointment_count');

    return [
      'summary' => $summary,
      'totals' => [
        'assigned_orders' => $totalAssignedOrders,
        'assigned_with_appointment' => $totalAssignedWithAppointment,
      ],
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function buildOverdueStageOrdersData(Request $request): array
  {
    $now = Carbon::now();
    $configuredStatuses = collect($this->overdueStageStatusConfigs())->keyBy('status');
    $allStatuses = collect(OrderStatusEnum::cases())->map(fn (OrderStatusEnum $status) => $status->value)->all();
    $orderTypes = collect(OrderTypeEnum::cases())->map(fn (OrderTypeEnum $type) => $type->value)->all();
    $productLines = collect(ProductLineEnum::cases())->map(fn (ProductLineEnum $line) => $line->value)->all();
    $sellerId = $request->integer('seller_id') ?: null;
    $overdueOnly = $request->boolean('overdue_only');
    $selectedStatuses = $this->resolveOverdueStageArrayFilter($request, 'statuses', $allStatuses);
    $selectedOrderTypes = $this->resolveOverdueStageArrayFilter($request, 'order_types', $orderTypes);
    $selectedProductLines = $this->resolveOverdueStageArrayFilter($request, 'product_lines', $productLines);

    $sellers = User::role(RoleEnum::OWNER->value)
      ->where('status', StatusUserEnum::ACTIVE->value)
      ->orderBy('name')
      ->get(['id', 'name'])
      ->map(fn (User $user) => [
        'id' => $user->id,
        'name' => $user->name,
      ]);

    $orders = Order::query()
      ->with([
        'owners:id,name',
        'user:id,name',
        'orderStatus' => function ($query) use ($allStatuses) {
          $query
            ->select('id', 'order_id', 'status', 'user_id', 'created_at')
            ->whereIn('status', $allStatuses)
            ->with('user:id,name')
            ->orderBy('created_at');
        },
      ])
      ->when($sellerId, function ($query) use ($sellerId) {
        $query->whereHas('owners', function ($ownerQuery) use ($sellerId) {
          $ownerQuery->where('users.id', $sellerId);
        });
      })
      ->when($selectedStatuses !== [], fn ($query) => $query->whereIn('status', $selectedStatuses))
      ->when($selectedOrderTypes !== [], fn ($query) => $query->whereIn('order_type', $selectedOrderTypes))
      ->when($selectedProductLines !== [], fn ($query) => $query->whereIn('product_line', $selectedProductLines))
      ->get([
        'id',
        'name',
        'status',
        'order_type',
        'product_line',
        'project_amount',
        'schedule_appointment',
        'created_at',
      ]);

    $groupStatuses = collect($this->reportPipelineStatusOrder())
      ->merge($allStatuses)
      ->merge($orders->pluck('status')->filter()->unique())
      ->unique()
      ->when($selectedStatuses !== [], fn (Collection $statuses) => $statuses->filter(fn (string $status) => in_array($status, $selectedStatuses, true)))
      ->values();

    $groups = $groupStatuses
      ->map(function (string $status) use ($orders, $now, $configuredStatuses, $overdueOnly) {
        $config = $configuredStatuses->get($status, [
          'status' => $status,
          'is_configured' => false,
          'hours' => null,
          'threshold_label' => 'Not configured',
          'note' => 'No overdue threshold is configured for this status.',
        ]);

        $rows = $orders
          ->filter(fn (Order $order) => $order->status === $status)
          ->map(fn (Order $order) => $this->mapOverdueStageOrderRow($order, $config, $now))
          ->when($overdueOnly, fn (Collection $rows) => $rows->filter(fn (array $row) => $row['is_overdue']))
          ->sortByDesc('days_in_stage')
          ->values();

        return [
          'status' => $status,
          'threshold_label' => $config['threshold_label'],
          'note' => $config['note'],
          'is_configured' => $config['is_configured'],
          'count' => $rows->count(),
          'overdue_count' => $rows->where('is_overdue', true)->count(),
          'amount_total' => $rows->sum('project_amount'),
          'rows' => $rows,
        ];
      })
      ->filter(fn (array $group) => $selectedStatuses !== [] || $group['count'] > 0)
      ->values();

    return [
      'generatedAt' => $now->toDateTimeString(),
      'sellers' => $sellers,
      'statusOptions' => collect($this->reportPipelineStatusOrder())->merge($allStatuses)->unique()->values(),
      'orderTypeOptions' => $orderTypes,
      'productLineOptions' => $productLines,
      'filters' => [
        'seller_id' => $sellerId,
        'overdue_only' => $overdueOnly,
        'statuses' => $selectedStatuses,
        'order_types' => $selectedOrderTypes,
        'product_lines' => $selectedProductLines,
      ],
      'totals' => [
        'statuses' => $groups->count(),
        'configured_statuses' => $groups->where('is_configured', true)->count(),
        'orders' => $groups->sum('count'),
        'overdue_orders' => $groups->sum('overdue_count'),
        'amount' => $groups->sum('amount_total'),
      ],
      'groups' => $groups,
    ];
  }

  private function resolveOverdueStageArrayFilter(Request $request, string $key, array $allowedValues): array
  {
    $value = $request->input($key, []);
    $values = is_array($value) ? $value : [$value];

    return collect($values)
      ->flatMap(fn ($item) => is_string($item) ? explode(',', $item) : [$item])
      ->map(fn ($item) => trim((string) $item))
      ->filter(fn (string $item) => $item !== '' && in_array($item, $allowedValues, true))
      ->unique()
      ->values()
      ->all();
  }

  private function reportPipelineStatusOrder(): array
  {
    return [
      OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
      OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
      OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
      OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
      OrderStatusEnum::QUALIFIED->value,
      OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value,
      OrderStatusEnum::PENDING_ASSIGNMENT->value,
      OrderStatusEnum::REQUEST_RE_SCHEDULE->value,
      OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
      OrderStatusEnum::FOLLOW_UP->value,
      OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
      OrderStatusEnum::STAND_BY->value,
      OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value,
      OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
      OrderStatusEnum::LOST_CONTRACT->value,
      OrderStatusEnum::PENDING_FINANCING_OR_DEPOSIT->value,
      OrderStatusEnum::PENDING_HOA_APPROVAL->value,
      OrderStatusEnum::RECTIFICATION_OF_MEASURES->value,
      OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
      OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
      OrderStatusEnum::FILE_REVIEW->value,
      OrderStatusEnum::CLOSED_WON->value,
      OrderStatusEnum::ACCOUNT_RECEIPT->value,
      OrderStatusEnum::REVIEW->value,
      OrderStatusEnum::PLANNED->value,
      OrderStatusEnum::REPLANNED->value,
      OrderStatusEnum::MATERIALS_RECEIVED->value,
      OrderStatusEnum::CONFIRMED->value,
      OrderStatusEnum::RESCHEDULE->value,
      OrderStatusEnum::EXECUTION->value,
      OrderStatusEnum::ON_HOLD->value,
      OrderStatusEnum::SUPERVISION->value,
      OrderStatusEnum::INSPECTION->value,
      OrderStatusEnum::FINISH->value,
      OrderStatusEnum::FINAL_INSPECTION->value,
      OrderStatusEnum::FINAL_COLLECT->value,
      OrderStatusEnum::COMPLETE->value,
    ];
  }

  private function overdueStageStatusConfigs(): array
  {
    return [
      [
        'status' => OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        'is_configured' => true,
        'hours' => 24,
        'threshold_label' => '24 hours',
        'note' => 'Overdue after 24 hours from when the order entered NEW REQUEST.',
      ],
      [
        'status' => OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
        'is_configured' => true,
        'hours' => 72,
        'threshold_label' => '72 hours (3 days)',
        'note' => 'Overdue after 72 hours from when the order entered REQUEST FOLLOW UP.',
      ],
      [
        'status' => OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
        'is_configured' => true,
        'hours' => 14 * 24,
        'threshold_label' => '14 days',
        'note' => 'Overdue after 14 days from when the order entered REQUEST STAND BY.',
      ],
      [
        'status' => OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
        'is_configured' => true,
        'hours' => null,
        'threshold_label' => 'Residential: 2 days. Commercial: 7 days.',
        'note' => 'Residential uses appointment date when available; otherwise status entry date. Commercial uses status entry date.',
      ],
      [
        'status' => OrderStatusEnum::FOLLOW_UP->value,
        'is_configured' => true,
        'hours' => 45 * 24,
        'threshold_label' => '45 days',
        'note' => 'Overdue is calculated from the first FOLLOW UP date, matching the board color rule.',
      ],
      [
        'status' => OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
        'is_configured' => true,
        'hours' => 45 * 24,
        'threshold_label' => '45 days',
        'note' => 'Overdue is calculated from the first FOLLOW UP date, matching the board color rule.',
      ],
      [
        'status' => OrderStatusEnum::STAND_BY->value,
        'is_configured' => true,
        'hours' => 120 * 24,
        'threshold_label' => '120 days',
        'note' => 'Overdue after 120 days from when the order entered STAND BY.',
      ],
    ];
  }

  private function mapOverdueStageOrderRow(Order $order, array $config, Carbon $now): array
  {
    $stageEnteredAt = $this->resolveOrderCurrentStatusEnteredAt($order);
    [$referenceAt, $thresholdHours] = $this->resolveOverdueStageReference($order, $config, $stageEnteredAt);
    $isOverdue = $referenceAt !== null
      && $thresholdHours !== null
      && $referenceAt->copy()->addHours($thresholdHours)->lessThanOrEqualTo($now);
    $sellerNames = $order->owners->pluck('name')->filter()->implode(', ');
    $creatorStatusEntry = $order->orderStatus
      ->sortBy('created_at')
      ->first();
    $creatorName = trim((string) ($creatorStatusEntry?->user?->name ?? $order->user?->name ?? ''));
    $groupLabel = $sellerNames !== '' ? $sellerNames : ($creatorName !== '' ? $creatorName : 'Unassigned');
    $groupSource = $sellerNames !== '' ? 'seller' : 'creator';

    return [
      'id' => $order->id,
      'order_name' => $order->name,
      'order_label' => $order->name ? "#{$order->id} - {$order->name}" : "#{$order->id}",
      'status' => $order->status,
      'order_type' => $order->order_type,
      'product_line' => $order->product_line,
      'project_amount' => (float) ($order->project_amount ?? 0),
      'seller_name' => $sellerNames,
      'created_by_name' => $creatorName,
      'group_label' => $groupLabel,
      'group_source' => $groupSource,
      'days_in_stage' => $stageEnteredAt?->diffInDays($now) ?? 0,
      'created_at' => $order->created_at?->toDateTimeString(),
      'stage_entered_at' => $stageEnteredAt?->toDateTimeString(),
      'is_overdue' => $isOverdue,
    ];
  }

  private function resolveOrderCurrentStatusEnteredAt(Order $order): ?Carbon
  {
    $statusHistoryEntry = $order->orderStatus
      ->where('status', $order->status)
      ->sortByDesc('created_at')
      ->first();

    return $statusHistoryEntry?->created_at
      ? Carbon::parse($statusHistoryEntry->created_at)
      : ($order->created_at ? Carbon::parse($order->created_at) : null);
  }

  private function resolveOverdueStageReference(Order $order, array $config, ?Carbon $stageEnteredAt): array
  {
    if ($config['status'] === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value) {
      $orderType = strtoupper(trim((string) $order->order_type));

      if ($orderType === OrderTypeEnum::RESIDENTIAL->value) {
        $appointmentAt = $order->schedule_appointment
          ? Carbon::parse($order->schedule_appointment)
          : null;

        return [$appointmentAt ?? $stageEnteredAt, 48];
      }

      if ($orderType === OrderTypeEnum::COMMERCIAL->value) {
        return [$stageEnteredAt, 168];
      }

      return [$stageEnteredAt, null];
    }

    if (in_array($config['status'], [OrderStatusEnum::FOLLOW_UP->value, OrderStatusEnum::FOLLOW_UP_PROJECTS->value], true)) {
      $followUpStartedAt = $order->orderStatus
        ->where('status', OrderStatusEnum::FOLLOW_UP->value)
        ->sortBy('created_at')
        ->first()?->created_at;

      return [
        $followUpStartedAt ? Carbon::parse($followUpStartedAt) : null,
        $config['hours'],
      ];
    }

    return [$stageEnteredAt, $config['hours']];
  }

  private function buildInstallerConfirmedSummaryData(Carbon $startDate, Carbon $endDate): array
  {
    $plannedOrders = OrderStatus::query()
      ->select('order_id')
      ->where('order_status.status', OrderStatusEnum::PLANNED->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $confirmedOrders = OrderStatus::query()
      ->select('order_id')
      ->where('order_status.status', OrderStatusEnum::CONFIRMED->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $completedOrders = OrderStatus::query()
      ->select('order_id')
      ->where('order_status.status', OrderStatusEnum::COMPLETE->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $orderProductsTotals = OrderProduct::query()
      ->select('order_id', DB::raw('SUM(total_price + extra_work_price) as products_total'))
      ->whereNull('deleted_at')
      ->groupBy('order_id');

    $firstInstallationTeam = DB::table('installation_teams_orders')
      ->select('order_id', DB::raw('MIN(id) as first_installation_team_order_id'))
      ->whereNull('deleted_at')
      ->groupBy('order_id');

    $amountExpression = 'COALESCE(order_products_totals.products_total, 0)'
      . ' + COALESCE(orders.additional_travel_costs, 0)'
      . ' + COALESCE(CASE WHEN orders.is_new_travel_cost = 1 THEN orders.new_travel_cost ELSE travel_costs.price END, 0)';

    $pickupOrDeliveryQualifiedOrders = DB::query()
      ->fromSub(
        (clone $confirmedOrders)->union(clone $completedOrders),
        'pickup_or_delivery_qualified_orders'
      )
      ->select('pickup_or_delivery_qualified_orders.order_id');

    $summary = Order::query()
      ->joinSub($confirmedOrders, 'confirmed_orders', function ($join) {
        $join->on('confirmed_orders.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($completedOrders, 'completed_orders', function ($join) {
        $join->on('completed_orders.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($firstInstallationTeam, 'first_installation_team', function ($join) {
        $join->on('first_installation_team.order_id', '=', 'orders.id');
      })
      ->leftJoin('installation_teams_orders as first_installation_team_order', 'first_installation_team_order.id', '=', 'first_installation_team.first_installation_team_order_id')
      ->leftJoin('installation_teams', 'installation_teams.id', '=', 'first_installation_team_order.installation_team_id')
      ->leftJoin('users', 'users.id', '=', 'installation_teams.user_id')
      ->leftJoin('travel_costs', 'travel_costs.id', '=', 'orders.travel_cost_id')
      ->leftJoinSub($orderProductsTotals, 'order_products_totals', function ($join) {
        $join->on('order_products_totals.order_id', '=', 'orders.id');
      })
      ->whereNull('orders.deleted_at')
      ->where(function ($query) {
        $query->whereNotNull('first_installation_team.first_installation_team_order_id')
          ->orWhereNotIn('orders.service', [ServiceEnum::PICKUP->value, ServiceEnum::DELIVERY->value]);
      })
      ->select(
        'installation_teams.id',
        'installation_teams.company_name',
        'users.name as installer_name',
        DB::raw('COUNT(*) as confirmed_orders'),
        DB::raw('COUNT(DISTINCT completed_orders.order_id) as completed_orders'),
        DB::raw('SUM(' . $amountExpression . ') as assigned_amount')
      )
      ->groupBy('installation_teams.id', 'installation_teams.company_name', 'users.name')
      ->orderBy('users.name')
      ->get();

    $pickupOrDeliverySummary = Order::query()
      ->joinSub($pickupOrDeliveryQualifiedOrders, 'pickup_or_delivery_qualified_orders', function ($join) {
        $join->on('pickup_or_delivery_qualified_orders.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($firstInstallationTeam, 'first_installation_team', function ($join) {
        $join->on('first_installation_team.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($completedOrders, 'completed_orders', function ($join) {
        $join->on('completed_orders.order_id', '=', 'orders.id');
      })
      ->leftJoin('travel_costs', 'travel_costs.id', '=', 'orders.travel_cost_id')
      ->leftJoinSub($orderProductsTotals, 'order_products_totals', function ($join) {
        $join->on('order_products_totals.order_id', '=', 'orders.id');
      })
      ->whereIn('orders.service', [ServiceEnum::PICKUP->value, ServiceEnum::DELIVERY->value])
      ->whereNull('first_installation_team.first_installation_team_order_id')
      ->select(
        DB::raw('COUNT(DISTINCT orders.id) as confirmed_orders'),
        DB::raw('SUM(CASE WHEN completed_orders.order_id IS NOT NULL THEN 1 ELSE 0 END) as completed_orders'),
        DB::raw('SUM(' . $amountExpression . ') as assigned_amount')
      )
      ->first();

    if ($pickupOrDeliverySummary && (int) $pickupOrDeliverySummary->confirmed_orders > 0) {
      $summary->push((object) [
        'id' => null,
        'company_name' => null,
        'installer_name' => 'PICKUP OR DELIVERY ONLY',
        'confirmed_orders' => (int) $pickupOrDeliverySummary->confirmed_orders,
        'completed_orders' => (int) $pickupOrDeliverySummary->completed_orders,
        'assigned_amount' => (float) ($pickupOrDeliverySummary->assigned_amount ?? 0),
      ]);
    }

    $totalConfirmed = (int) $summary->sum('confirmed_orders');
    $totalCompleted = (int) $summary->sum('completed_orders');
    $totalAssigned = (float) $summary->sum('assigned_amount');

    return [
      'summary' => $summary,
      'totalConfirmed' => $totalConfirmed,
      'totalCompleted' => $totalCompleted,
      'totalAssigned' => $totalAssigned,
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function buildOwnerAssignedSummaryData(Carbon $startDate, Carbon $endDate): array
  {
    $ownerAssignments = DB::table('owner_user')
      ->select('user_id', 'order_id')
      ->whereNull('deleted_at')
      ->groupBy('user_id', 'order_id');

    $estimateOrdersInRange = OrderStatus::query()
      ->select('order_id')
      ->where('status', OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $closedWonOrders = OrderStatus::query()
      ->select('order_id')
      ->where('status', OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $amountExpression = 'COALESCE(orders.project_amount, 0)';
    $estimatedCondition = $amountExpression . ' > 0';

    $summary = DB::query()
      ->fromSub($ownerAssignments, 'owner_assignments')
      ->joinSub($estimateOrdersInRange, 'estimate_orders_in_range', function ($join) {
        $join->on('estimate_orders_in_range.order_id', '=', 'owner_assignments.order_id');
      })
      ->join('users', 'users.id', '=', 'owner_assignments.user_id')
      ->join('orders', 'orders.id', '=', 'owner_assignments.order_id')
      ->leftJoinSub($closedWonOrders, 'closed_won_orders', function ($join) {
        $join->on('closed_won_orders.order_id', '=', 'orders.id');
      })
      ->whereNull('orders.deleted_at')
      ->where('orders.counts_for_owner_commission', true)
      ->select(
        'users.id as owner_id',
        'users.name as owner_name',
        DB::raw('COUNT(owner_assignments.order_id) as estimate_orders'),
        DB::raw('SUM(CASE WHEN ' . $estimatedCondition . ' THEN 1 ELSE 0 END) as estimated_clients'),
        DB::raw('SUM(CASE WHEN ' . $estimatedCondition . ' THEN ' . $amountExpression . ' ELSE 0 END) as estimate_amount'),
        DB::raw('SUM(CASE WHEN ' . $estimatedCondition . ' AND closed_won_orders.order_id IS NOT NULL THEN 1 ELSE 0 END) as closed_won_orders'),
        DB::raw('SUM(CASE WHEN ' . $estimatedCondition . ' AND closed_won_orders.order_id IS NOT NULL THEN (' . $amountExpression . ') ELSE 0 END) as closed_won_amount')
      )
      ->groupBy('users.id', 'users.name')
      ->orderBy('users.name')
      ->get();

    $totalEstimateOrders = DB::query()
      ->fromSub($ownerAssignments, 'owner_assignments')
      ->joinSub($estimateOrdersInRange, 'estimate_orders_in_range', function ($join) {
        $join->on('estimate_orders_in_range.order_id', '=', 'owner_assignments.order_id');
      })
      ->join('orders', 'orders.id', '=', 'owner_assignments.order_id')
      ->whereNull('orders.deleted_at')
      ->where('orders.counts_for_owner_commission', true)
      ->count();

    $totalEstimatedClients = DB::query()
      ->fromSub($ownerAssignments, 'owner_assignments')
      ->joinSub($estimateOrdersInRange, 'estimate_orders_in_range', function ($join) {
        $join->on('estimate_orders_in_range.order_id', '=', 'owner_assignments.order_id');
      })
      ->join('orders', 'orders.id', '=', 'owner_assignments.order_id')
      ->whereNull('orders.deleted_at')
      ->where('orders.counts_for_owner_commission', true)
      ->whereRaw($estimatedCondition)
      ->count();

    $totalEstimateAmount = DB::query()
      ->fromSub($ownerAssignments, 'owner_assignments')
      ->joinSub($estimateOrdersInRange, 'estimate_orders_in_range', function ($join) {
        $join->on('estimate_orders_in_range.order_id', '=', 'owner_assignments.order_id');
      })
      ->join('orders', 'orders.id', '=', 'owner_assignments.order_id')
      ->whereNull('orders.deleted_at')
      ->where('orders.counts_for_owner_commission', true)
      ->whereRaw($estimatedCondition)
      ->select(DB::raw('SUM(' . $amountExpression . ') as total_estimate_amount'))
      ->value('total_estimate_amount') ?? 0;

    $totalClosedWonOrders = DB::query()
      ->fromSub($ownerAssignments, 'owner_assignments')
      ->joinSub($estimateOrdersInRange, 'estimate_orders_in_range', function ($join) {
        $join->on('estimate_orders_in_range.order_id', '=', 'owner_assignments.order_id');
      })
      ->join('orders', 'orders.id', '=', 'owner_assignments.order_id')
      ->joinSub($closedWonOrders, 'closed_won_orders', function ($join) {
        $join->on('closed_won_orders.order_id', '=', 'orders.id');
      })
      ->whereNull('orders.deleted_at')
      ->where('orders.counts_for_owner_commission', true)
      ->whereRaw($estimatedCondition)
      ->count();

    $totalClosedWonAmount = DB::query()
      ->fromSub($ownerAssignments, 'owner_assignments')
      ->joinSub($estimateOrdersInRange, 'estimate_orders_in_range', function ($join) {
        $join->on('estimate_orders_in_range.order_id', '=', 'owner_assignments.order_id');
      })
      ->join('orders', 'orders.id', '=', 'owner_assignments.order_id')
      ->joinSub($closedWonOrders, 'closed_won_orders', function ($join) {
        $join->on('closed_won_orders.order_id', '=', 'orders.id');
      })
      ->whereNull('orders.deleted_at')
      ->where('orders.counts_for_owner_commission', true)
      ->whereRaw($estimatedCondition)
      ->select(DB::raw('SUM(' . $amountExpression . ') as total_closed_won_amount'))
      ->value('total_closed_won_amount') ?? 0;

    $summary = $summary->map(function ($item) {
      $estimatedClients = (int) $item->estimated_clients;
      $estimateAmount = (float) $item->estimate_amount;
      $closedWonOrders = (int) $item->closed_won_orders;
      $closedWonAmount = (float) $item->closed_won_amount;

      $item->closed_won_orders_percentage = $estimatedClients > 0
        ? round(($closedWonOrders / $estimatedClients) * 100, 2)
        : 0.0;
      $item->closed_won_amount_percentage = $estimateAmount > 0
        ? round(($closedWonAmount / $estimateAmount) * 100, 2)
        : 0.0;

      return $item;
    });

    $totalClosedWonOrdersPercentage = $totalEstimatedClients > 0
      ? round(($totalClosedWonOrders / $totalEstimatedClients) * 100, 2)
      : 0.0;
    $totalClosedWonAmountPercentage = $totalEstimateAmount > 0
      ? round(($totalClosedWonAmount / $totalEstimateAmount) * 100, 2)
      : 0.0;

    return [
      'summary' => $summary,
      'totalEstimateOrders' => $totalEstimateOrders,
      'totalEstimatedClients' => $totalEstimatedClients,
      'totalEstimateAmount' => $totalEstimateAmount,
      'totalClosedWonOrders' => $totalClosedWonOrders,
      'totalClosedWonAmount' => $totalClosedWonAmount,
      'totalClosedWonOrdersPercentage' => $totalClosedWonOrdersPercentage,
      'totalClosedWonAmountPercentage' => $totalClosedWonAmountPercentage,
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function buildSupervisorAssignedSummaryData(Carbon $startDate, Carbon $endDate): array
  {
    $plannedOrders = OrderStatus::query()
      ->select('order_status.order_id')
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->whereNull('orders.deleted_at')
      ->where('order_status.status', OrderStatusEnum::PLANNED->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->distinct();

    $confirmedOrders = OrderStatus::query()
      ->select('order_status.order_id')
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->whereNull('orders.deleted_at')
      ->where('order_status.status', OrderStatusEnum::CONFIRMED->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->distinct();

    $completedOrders = OrderStatus::query()
      ->select('order_status.order_id')
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->whereNull('orders.deleted_at')
      ->where('order_status.status', OrderStatusEnum::COMPLETE->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->distinct();

    $currentExecutionExcludedStatuses = [
      OrderStatusEnum::CONFIRMED->value,
      OrderStatusEnum::PLANNED->value,
      OrderStatusEnum::RESCHEDULE->value,
      OrderStatusEnum::REPLANNED->value,
      OrderStatusEnum::COMPLETE->value,
      OrderStatusEnum::MATERIALS_RECEIVED->value,
    ];

    $inspectionOrders = OrderStatus::query()
      ->select('order_status.order_id')
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->whereNull('orders.deleted_at')
      ->where('order_status.status', OrderStatusEnum::INSPECTION->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->distinct();

    $executionNotCompletedOrders = DB::query()
      ->fromSub(
        Order::query()
          ->select('orders.id as order_id')
          ->whereNull('orders.deleted_at')
          ->whereNotIn('orders.status', $currentExecutionExcludedStatuses),
        'execution'
      )
      ->select('execution.order_id');

    $inspectionNotCompletedOrders = DB::query()
      ->fromSub($inspectionOrders, 'inspection')
      ->leftJoinSub($completedOrders, 'completed', function ($join) {
        $join->on('completed.order_id', '=', 'inspection.order_id');
      })
      ->whereNull('completed.order_id')
      ->select('inspection.order_id');

    $pickupOrDeliveryQualifiedOrders = DB::query()
      ->fromSub(
        (clone $plannedOrders)->union(clone $confirmedOrders),
        'pickup_or_delivery_qualified_orders'
      )
      ->select('pickup_or_delivery_qualified_orders.order_id');

    $summary = Order::query()
      ->leftJoin('users', 'users.id', '=', 'orders.supervisor_id')
      ->leftJoinSub($confirmedOrders, 'confirmed_orders', function ($join) {
        $join->on('confirmed_orders.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($completedOrders, 'completed_orders', function ($join) {
        $join->on('completed_orders.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($executionNotCompletedOrders, 'execution_not_completed_orders', function ($join) {
        $join->on('execution_not_completed_orders.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($inspectionNotCompletedOrders, 'inspection_not_completed_orders', function ($join) {
        $join->on('inspection_not_completed_orders.order_id', '=', 'orders.id');
      })
      ->where(function ($query) {
        $query->whereNotNull('confirmed_orders.order_id')
          ->orWhereNotNull('completed_orders.order_id')
          ->orWhereNotNull('execution_not_completed_orders.order_id')
          ->orWhereNotNull('inspection_not_completed_orders.order_id');
      })
      ->whereNotNull('orders.supervisor_id')
      ->where('users.status', StatusUserEnum::ACTIVE->value)
      ->select(
        'orders.supervisor_id',
        'users.name as supervisor_name',
        DB::raw('SUM(CASE WHEN confirmed_orders.order_id IS NOT NULL THEN 1 ELSE 0 END) as confirmed_orders'),
        DB::raw('SUM(CASE WHEN completed_orders.order_id IS NOT NULL AND confirmed_orders.order_id IS NOT NULL THEN 1 ELSE 0 END) as confirmed_completed_orders'),
        DB::raw('COUNT(execution_not_completed_orders.order_id) as execution_not_completed_orders'),
        DB::raw('COUNT(inspection_not_completed_orders.order_id) as inspection_not_completed_orders')
      )
      ->groupBy('orders.supervisor_id', 'users.name')
      ->orderBy('users.name')
      ->get();

    $pickupOrDeliverySummary = Order::query()
      ->joinSub($pickupOrDeliveryQualifiedOrders, 'pickup_or_delivery_qualified_orders', function ($join) {
        $join->on('pickup_or_delivery_qualified_orders.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($completedOrders, 'completed_orders', function ($join) {
        $join->on('completed_orders.order_id', '=', 'orders.id');
      })
      ->whereNull('orders.supervisor_id')
      ->whereIn('orders.service', [ServiceEnum::PICKUP->value, ServiceEnum::DELIVERY->value])
      ->select(
        DB::raw('COUNT(*) as confirmed_orders'),
        DB::raw('SUM(CASE WHEN completed_orders.order_id IS NOT NULL THEN 1 ELSE 0 END) as confirmed_completed_orders')
      )
      ->first();

    if ($pickupOrDeliverySummary && (int) $pickupOrDeliverySummary->confirmed_orders > 0) {
      $summary->push((object) [
        'supervisor_id' => null,
        'supervisor_name' => 'PICKUP OR DELIVERY ONLY',
        'confirmed_orders' => (int) $pickupOrDeliverySummary->confirmed_orders,
        'confirmed_completed_orders' => (int) $pickupOrDeliverySummary->confirmed_completed_orders,
        'execution_not_completed_orders' => 0,
        'inspection_not_completed_orders' => 0,
      ]);
    }

    $currentInspectionBySupervisor = Order::query()
      ->leftJoin('users', 'users.id', '=', 'orders.supervisor_id')
      ->whereNull('orders.deleted_at')
      ->where('orders.status', OrderStatusEnum::INSPECTION->value)
      ->whereNotNull('orders.supervisor_id')
      ->where('users.status', StatusUserEnum::ACTIVE->value)
      ->select('orders.supervisor_id', DB::raw('COUNT(*) as total'))
      ->groupBy('orders.supervisor_id')
      ->get()
      ->mapWithKeys(function ($row) {
        $key = $row->supervisor_id === null ? 'null' : (string) $row->supervisor_id;

        return [$key => (int) $row->total];
      });

    $summary = $summary->map(function ($item) use ($currentInspectionBySupervisor) {
      if (($item->supervisor_name ?? null) === 'PICKUP OR DELIVERY ONLY') {
        $item->inspection_not_completed_orders = 0;

        return $item;
      }

      $key = $item->supervisor_id === null ? 'null' : (string) $item->supervisor_id;
      $item->inspection_not_completed_orders = (int) ($currentInspectionBySupervisor->get($key, 0));

      return $item;
    });

    $totalConfirmed = (int) $summary->sum('confirmed_orders');
    $totalConfirmedCompleted = (int) $summary->sum('confirmed_completed_orders');

    $totalExecutionNotCompleted = Order::query()
      ->leftJoin('users', 'users.id', '=', 'orders.supervisor_id')
      ->whereNull('orders.deleted_at')
      ->whereNotNull('orders.supervisor_id')
      ->where('users.status', StatusUserEnum::ACTIVE->value)
      ->whereNotIn('orders.status', $currentExecutionExcludedStatuses)
      ->count();

    $totalInspectionNotCompleted = Order::query()
      ->leftJoin('users', 'users.id', '=', 'orders.supervisor_id')
      ->whereNull('orders.deleted_at')
      ->where('orders.status', OrderStatusEnum::INSPECTION->value)
      ->whereNotNull('orders.supervisor_id')
      ->where('users.status', StatusUserEnum::ACTIVE->value)
      ->count();

    return [
      'summary' => $summary,
      'totalConfirmed' => $totalConfirmed,
      'totalConfirmedCompleted' => $totalConfirmedCompleted,
      'totalExecutionNotCompleted' => $totalExecutionNotCompleted,
      'totalInspectionNotCompleted' => $totalInspectionNotCompleted,
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function buildReplannedOrdersSummaryData(Carbon $startDate, Carbon $endDate): array
  {
    $plannedStatus = addslashes(OrderStatusEnum::PLANNED->value);

    $rows = DB::table('order_status as replanned')
      ->join('orders', function ($join) {
        $join->on('orders.id', '=', 'replanned.order_id')
          ->whereNull('orders.deleted_at');
      })
      ->leftJoin('order_status as planned', function ($join) use ($plannedStatus) {
        $join->on('planned.id', '=', DB::raw("(
          SELECT p.id
          FROM order_status p
          WHERE p.order_id = replanned.order_id
            AND p.status = '{$plannedStatus}'
            AND p.deleted_at IS NULL
            AND p.created_at <= replanned.created_at
          ORDER BY p.created_at DESC, p.id DESC
          LIMIT 1
        )"));
      })
      ->where('replanned.status', OrderStatusEnum::REPLANNED->value)
      ->whereNull('replanned.deleted_at')
      ->whereBetween('replanned.created_at', [$startDate, $endDate])
      ->select(
        'replanned.id',
        'replanned.order_id',
        'orders.order_number',
        'orders.name as order_name',
        'replanned.replanned_reasons',
        'replanned.created_at as replanned_at',
        'planned.pickup_date as planned_pickup_date',
        'planned.start_date as planned_start_date',
        'planned.end_date as planned_end_date',
        'replanned.pickup_date as replanned_pickup_date',
        'replanned.start_date as replanned_start_date',
        'replanned.end_date as replanned_end_date'
      )
      ->orderByDesc('replanned.created_at')
      ->get()
      ->map(function ($row) {
        $replannedReasons = $this->normalizeReplannedReasonsForReport($row->replanned_reasons);

        return [
          'id' => (int) $row->id,
          'order_id' => (int) $row->order_id,
          'order_number' => $row->order_number,
          'order_name' => $row->order_name,
          'replanned_at' => $row->replanned_at ? Carbon::parse($row->replanned_at)->toDateTimeString() : null,
          'replanned_reasons' => $replannedReasons,
          'replanned_reasons_label' => empty($replannedReasons) ? '-' : implode(', ', $replannedReasons),
          'planned_pickup_date' => $row->planned_pickup_date ? Carbon::parse($row->planned_pickup_date)->toDateString() : null,
          'planned_start_date' => $row->planned_start_date ? Carbon::parse($row->planned_start_date)->toDateString() : null,
          'planned_end_date' => $row->planned_end_date ? Carbon::parse($row->planned_end_date)->toDateString() : null,
          'replanned_pickup_date' => $row->replanned_pickup_date ? Carbon::parse($row->replanned_pickup_date)->toDateString() : null,
          'replanned_start_date' => $row->replanned_start_date ? Carbon::parse($row->replanned_start_date)->toDateString() : null,
          'replanned_end_date' => $row->replanned_end_date ? Carbon::parse($row->replanned_end_date)->toDateString() : null,
        ];
      })
      ->values();

    $reasonCounts = $rows
      ->flatMap(static fn (array $row) => $row['replanned_reasons'] ?? [])
      ->map(static fn (string $reason) => strtoupper(trim($reason)))
      ->filter(static fn (string $reason) => $reason !== '')
      ->countBy()
      ->sortKeys()
      ->all();

    return [
      'rows' => $rows,
      'totals' => [
        'total' => $rows->count(),
        'reason_counts' => $reasonCounts,
      ],
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function normalizeReplannedReasonsForReport(mixed $rawReasons): array
  {
    $values = [];

    if (is_array($rawReasons)) {
      $values = $rawReasons;
    } elseif (is_string($rawReasons) && $rawReasons !== '') {
      $decoded = json_decode($rawReasons, true);
      if (is_array($decoded)) {
        $values = $decoded;
      } else {
        $values = explode(',', $rawReasons);
      }
    }

    return collect($values)
      ->map(static fn ($reason) => strtoupper(trim((string) $reason)))
      ->filter(static fn (string $reason) => $reason !== '')
      ->unique()
      ->values()
      ->all();
  }


public function dropPayment($id)
{

  // Buscar el attachment por ID
  $attachment = InstallationPayment::find($id);
  //dd($attachment);

  // Verificar si el attachment existe
  if (!$attachment) {
    return redirect()
      ->back()
      ->with('error', 'Attachment not found');
  }

  // Obtener el usuario autenticado
  $user = auth()->user();

  if ($attachment->user_id === auth()->user()->id || $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::PAYMENT_COORDINATOR->value])) {
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
}
