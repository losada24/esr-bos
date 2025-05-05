<?php

namespace App\Http\Controllers;

use App\Actions\CreateBiweekly;
use App\Actions\UpdateBiweekly;
use App\Actions\UpdatePaymentInstaller;
use App\Enum\HistoryPaymentEnum;
use App\Enum\InstallerPaymentStatusEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Exports\InstallerExport;
use App\Exports\SupervisorExport;
use App\Exports\SupervisorExportPayment;
use App\Http\Requests\StoreInstallerPaymentRequest;
use App\Http\Resources\InstallationTeamCollection;
use App\Jobs\SendGmailEmail;
use App\Mail\InstallationPaidEmail;
use App\Mail\InstallationPayment as MailInstallationPayment;
use App\Mail\InstallationPaymentEmail;
use App\Models\Biweekly;
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
        //dd($order['installation_payments']);
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
