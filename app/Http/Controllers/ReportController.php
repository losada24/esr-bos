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
use App\Enum\PaymentStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Exports\InstallerExport;
use App\Exports\InstallerConfirmedSummaryExport;
use App\Exports\DailyOrderStatusSummaryExport;
use App\Exports\MarketingReportExport;
use App\Exports\OwnerAssignedSummaryExport;
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
        ->orderBy('users.name', 'asc')
        ->select('installation_teams.*') // Importante: evita conflictos en los campos seleccionados
        ->paginate()
        ->withQueryString()
      )
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

    $payment = InstallationPayment::where('order_id', $id)->get();


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
        $paymentPercentage = 0;
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

          if ($installationPayment->payment_status == PaymentStatusEnum::PAID->value) {
            $paymentPercentage += $payment['percentage_payment'];
          }
        }

        $getAllIntallerPaymentAmount = InstallationPayment::where('order_id', $order['id'])
          ->where('installation_team_id', $id)
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

            //dd( $pendingPaymentPercent);

          if($order['status'] == OrderStatusEnum::INSPECTION->value || $order['pre_inspection'] == 0 || $order['partial_payment_installation'] == 0){
            $pendingPaymentPercent = 0;
          }
          if($order['status'] == OrderStatusEnum::COMPLETE->value && ( $order['walk_trough'] == 0 || $order['final_payment_installation'] == 0)){
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
    $startDate = Carbon::parse($request->start_date);
    $endDate = Carbon::parse($request->end_date);

          /*$filteredOrderIds = Order::with(['orderStatus' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }])->get()->filter(function ($order) {
            $statuses = $order->orderStatus->pluck('status');
            return $statuses->contains('PLANNED');
        })->pluck('id');*/
        $filteredOrderIds = Order::whereHas('orderStatus', function ($q) use ($startDate, $endDate) {
          $q->where('status', 'CONFIRMED')
            ->whereBetween('created_at', [$startDate, $endDate]);
      })->pluck('id');
        
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
    $startDate = Carbon::parse($request->start_date)->startOfDay();
    $endDate = Carbon::parse($request->end_date)->endOfDay();

    $statuses = [
      OrderStatusEnum::PLANNED->value,
      OrderStatusEnum::CONFIRMED->value,
      OrderStatusEnum::COMPLETE->value,
    ];

    $confirmedOrders = OrderStatus::query()
      ->select('order_id')
      ->where('status', OrderStatusEnum::CONFIRMED->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $completedOrders = OrderStatus::query()
      ->select('order_id')
      ->where('status', OrderStatusEnum::COMPLETE->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $confirmedCompletedCount = DB::query()
      ->fromSub($confirmedOrders, 'confirmed')
      ->joinSub($completedOrders, 'completed', function ($join) {
        $join->on('completed.order_id', '=', 'confirmed.order_id');
      })
      ->count();

    $statusSummary = collect($statuses)->map(function ($status) use ($startDate, $endDate, $confirmedCompletedCount) {
      if ($status === OrderStatusEnum::COMPLETE->value) {
        return [
          'status' => $status,
          'count' => $confirmedCompletedCount,
        ];
      }

      $count = OrderStatus::where('status', $status)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->count();

      return [
        'status' => $status,
        'count' => $count,
      ];
    });

    return Inertia::render('Report/OrderStatusSummary', [
      'statusSummary' => $statusSummary,
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ]);
  }

  public function dailyOrderStatusSummary(Request $request)
  {
    [$startDate, $endDate] = $this->resolveDailyOrderStatusSummaryDateRange($request);
    $data = $this->buildDailyOrderStatusSummaryData($startDate, $endDate);

    return Inertia::render('Report/DailyOrderStatusSummary', [
      'dailySummary' => $data['dailySummary'],
      'totals' => $data['totals'],
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
    $pdfName = 'owner-assigned-summary.pdf';

    return $pdf->stream($pdfName);
  }

  public function ownerAssignedSummaryExcel(Request $request)
  {
    [$startDate, $endDate] = $this->resolveOwnerAssignedSummaryDateRange($request);
    $data = $this->buildOwnerAssignedSummaryData($startDate, $endDate);

    return Excel::download(
      new OwnerAssignedSummaryExport($data),
      'Owner Assigned Summary.xlsx',
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
    $cohort = OrderStatus::query()
      ->selectRaw('DATE(created_at) as summary_date, order_id')
      ->whereIn('status', [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::QUALIFIED->value,
      ])
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $cohortCounts = DB::query()
      ->fromSub($cohort, 'cohort')
      ->selectRaw('summary_date, COUNT(DISTINCT order_id) as total')
      ->groupBy('summary_date')
      ->orderBy('summary_date')
      ->get()
      ->keyBy('summary_date');

    $statusCounts = OrderStatus::query()
      ->selectRaw('DATE(created_at) as summary_date, status, COUNT(DISTINCT order_id) as total')
      ->whereIn('status', [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::QUALIFIED->value,
      ])
      ->whereBetween('created_at', [$startDate, $endDate])
      ->groupBy('summary_date', 'status')
      ->orderBy('summary_date')
      ->get();

    $estimateBase = OrderStatus::query()
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->selectRaw('DATE(order_status.created_at) as summary_date, order_status.order_id')
      ->where('order_status.status', OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value)
      ->whereNotNull('orders.schedule_appointment')
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->distinct();

    $estimateCounts = DB::query()
      ->fromSub($estimateBase, 'estimate')
      ->joinSub($cohort, 'cohort', function ($join) {
        $join->on('cohort.order_id', '=', 'estimate.order_id');
        $join->on('cohort.summary_date', '=', 'estimate.summary_date');
      })
      ->selectRaw('estimate.summary_date as summary_date, COUNT(DISTINCT estimate.order_id) as total')
      ->groupBy('estimate.summary_date')
      ->orderBy('estimate.summary_date')
      ->get()
      ->keyBy('summary_date');

    $daily = [];
    $current = $startDate->copy()->startOfDay();

    while ($current->lte($endDate)) {
      $dateKey = $current->toDateString();
      $daily[$dateKey] = [
        'date' => $dateKey,
        'new_request' => 0,
        'qualified' => 0,
        'estimate_appt_schedule' => 0,
        'total' => 0,
      ];
      $current->addDay();
    }

    foreach ($statusCounts as $row) {
      $dateKey = $row->summary_date;

      if (!isset($daily[$dateKey])) {
        continue;
      }

      if ($row->status === OrderStatusEnum::NEW_CUSTOMER_REQUEST->value) {
        $daily[$dateKey]['new_request'] = (int) $row->total;
      } elseif ($row->status === OrderStatusEnum::QUALIFIED->value) {
        $daily[$dateKey]['qualified'] = (int) $row->total;
      }
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

    $dailySummary = collect($daily)->values()->map(function ($row) {
      return [
        'date' => $row['date'],
        'new_request_qualified' => $row['total'],
        'qualified' => $row['qualified'],
        'estimate_appt_schedule' => $row['estimate_appt_schedule'],
      ];
    });

    $totals = [
      'total' => $dailySummary->sum('new_request_qualified'),
      'qualified' => $dailySummary->sum('qualified'),
      'estimate_appt_schedule' => $dailySummary->sum('estimate_appt_schedule'),
    ];

    return [
      'dailySummary' => $dailySummary,
      'totals' => $totals,
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
      'lostClients' => $lostClients,
      'totals' => [
        'total_clients' => $totalClientsInRange,
        'qualified_clients' => $qualifiedClients->count(),
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

  private function buildInstallerConfirmedSummaryData(Carbon $startDate, Carbon $endDate): array
  {
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

    $summary = OrderStatus::query()
      ->leftJoinSub($firstInstallationTeam, 'first_installation_team', function ($join) {
        $join->on('first_installation_team.order_id', '=', 'order_status.order_id');
      })
      ->leftJoin('installation_teams_orders as first_installation_team_order', 'first_installation_team_order.id', '=', 'first_installation_team.first_installation_team_order_id')
      ->leftJoin('installation_teams', 'installation_teams.id', '=', 'first_installation_team_order.installation_team_id')
      ->leftJoin('users', 'users.id', '=', 'installation_teams.user_id')
      ->leftJoin('orders', 'orders.id', '=', 'order_status.order_id')
      ->leftJoin('travel_costs', 'travel_costs.id', '=', 'orders.travel_cost_id')
      ->leftJoinSub($orderProductsTotals, 'order_products_totals', function ($join) {
        $join->on('order_products_totals.order_id', '=', 'orders.id');
      })
      ->where('order_status.status', OrderStatusEnum::CONFIRMED->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->select(
        'installation_teams.id',
        'installation_teams.company_name',
        'users.name as installer_name',
        DB::raw('COUNT(order_status.id) as confirmed_orders'),
        DB::raw('SUM(' . $amountExpression . ') as assigned_amount')
      )
      ->groupBy('installation_teams.id', 'installation_teams.company_name', 'users.name')
      ->orderBy('users.name')
      ->get();

    $totalConfirmed = OrderStatus::where('status', OrderStatusEnum::CONFIRMED->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->count();

    $totalAssigned = OrderStatus::query()
      ->join('orders', 'orders.id', '=', 'order_status.order_id')
      ->leftJoin('travel_costs', 'travel_costs.id', '=', 'orders.travel_cost_id')
      ->leftJoinSub($orderProductsTotals, 'order_products_totals', function ($join) {
        $join->on('order_products_totals.order_id', '=', 'orders.id');
      })
      ->where('order_status.status', OrderStatusEnum::CONFIRMED->value)
      ->whereBetween('order_status.created_at', [$startDate, $endDate])
      ->select(DB::raw('SUM(' . $amountExpression . ') as total_assigned'))
      ->value('total_assigned') ?? 0;

    return [
      'summary' => $summary,
      'totalConfirmed' => $totalConfirmed,
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
      ->where('status', OrderStatusEnum::CLOSED_WON->value)
      ->distinct();

    $amountExpression = 'COALESCE(orders.project_amount, 0)';

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
      ->select(
        'users.id as owner_id',
        'users.name as owner_name',
        DB::raw('COUNT(owner_assignments.order_id) as estimate_orders'),
        DB::raw('SUM(' . $amountExpression . ') as estimate_amount'),
        DB::raw('SUM(CASE WHEN closed_won_orders.order_id IS NOT NULL THEN 1 ELSE 0 END) as closed_won_orders'),
        DB::raw('SUM(CASE WHEN closed_won_orders.order_id IS NOT NULL THEN (' . $amountExpression . ') ELSE 0 END) as closed_won_amount')
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
      ->count();

    $totalEstimateAmount = DB::query()
      ->fromSub($ownerAssignments, 'owner_assignments')
      ->joinSub($estimateOrdersInRange, 'estimate_orders_in_range', function ($join) {
        $join->on('estimate_orders_in_range.order_id', '=', 'owner_assignments.order_id');
      })
      ->join('orders', 'orders.id', '=', 'owner_assignments.order_id')
      ->whereNull('orders.deleted_at')
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
      ->select(DB::raw('SUM(' . $amountExpression . ') as total_closed_won_amount'))
      ->value('total_closed_won_amount') ?? 0;

    return [
      'summary' => $summary,
      'totalEstimateOrders' => $totalEstimateOrders,
      'totalEstimateAmount' => $totalEstimateAmount,
      'totalClosedWonOrders' => $totalClosedWonOrders,
      'totalClosedWonAmount' => $totalClosedWonAmount,
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
  }

  private function buildSupervisorAssignedSummaryData(Carbon $startDate, Carbon $endDate): array
  {
    $confirmedOrders = OrderStatus::query()
      ->select('order_id')
      ->where('status', OrderStatusEnum::CONFIRMED->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $completedOrders = OrderStatus::query()
      ->select('order_id')
      ->where('status', OrderStatusEnum::COMPLETE->value)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->distinct();

    $summary = Order::query()
      ->leftJoin('users', 'users.id', '=', 'orders.supervisor_id')
      ->leftJoinSub($confirmedOrders, 'confirmed_orders', function ($join) {
        $join->on('confirmed_orders.order_id', '=', 'orders.id');
      })
      ->leftJoinSub($completedOrders, 'completed_orders', function ($join) {
        $join->on('completed_orders.order_id', '=', 'orders.id');
      })
      ->where(function ($query) {
        $query->whereNotNull('confirmed_orders.order_id')
          ->orWhereNotNull('completed_orders.order_id');
      })
      ->select(
        'orders.supervisor_id',
        'users.name as supervisor_name',
        DB::raw('COUNT(confirmed_orders.order_id) as confirmed_orders'),
        DB::raw('SUM(CASE WHEN confirmed_orders.order_id IS NOT NULL AND completed_orders.order_id IS NOT NULL THEN 1 ELSE 0 END) as confirmed_completed_orders')
      )
      ->groupBy('orders.supervisor_id', 'users.name')
      ->orderBy('users.name')
      ->get();

    $totalConfirmed = (clone $confirmedOrders)->count();

    $totalConfirmedCompleted = DB::query()
      ->fromSub($confirmedOrders, 'confirmed')
      ->joinSub($completedOrders, 'completed', function ($join) {
        $join->on('completed.order_id', '=', 'confirmed.order_id');
      })
      ->count();

    return [
      'summary' => $summary,
      'totalConfirmed' => $totalConfirmed,
      'totalConfirmedCompleted' => $totalConfirmedCompleted,
      'startDate' => $startDate->toDateString(),
      'endDate' => $endDate->toDateString(),
    ];
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
