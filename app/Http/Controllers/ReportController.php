<?php

namespace App\Http\Controllers;

use App\Actions\CreateBiweekly;
use App\Actions\UpdateBiweekly;
use App\Actions\UpdatePaymentInstaller;
use App\Enum\InstallerPaymentStatusEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Exports\InstallerExport;
use App\Exports\SupervisorExport;
use App\Http\Requests\StoreInstallerPaymentRequest;
use App\Http\Resources\InstallationTeamCollection;
use App\Models\Biweekly;
use App\Models\InstallationPayment;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\PaymentExtraField;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use App\Rules\ValidateInstallationPayment;
use Barryvdh\LaravelIdeHelper\Method;
use Inertia\Inertia;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Twilio\Rest\Api\V2010\Account\Call\PaymentInstance;
use Illuminate\Contracts\Validation\ValidationRule;

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
        if ($orders instanceof EloquentBuilder || $orders instanceof QueryBuilder) {
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
          ]
    ]);
    }
   
    public function export(Request $request, User $user) 
    {  
        return Excel::download( 
          new SupervisorExport($user->id), 
          'Supervisor '. $user->name . '.xlsx', 
          \Maatwebsite\Excel\Excel::XLSX
        );
    }


    public function showInstaller ($id) 
    {   $status = request()->get('status');
       $orderStatus = request()->get('order_status');
       $startDate = request()->get('start_date');
       $endDate = request()->get('end_date');
      //dd($startDate, $endDate );
          // Obtener las órdenes por supervisor
        $orders = $this->getOrdersByInstaller($id, $status , $startDate, $endDate, $orderStatus);
        //dd($orders);
        
        $name = request()->get('name');
        $paymentDate = request()->get('payment_date');
        

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
    {
          return Inertia::render('Report/Installer', [
            'installation_teams' => new InstallationTeamCollection(
              InstallationTeam::filter($request->only(['text']))
              ->orderBy('updated_at', 'desc')
              ->paginate()
              ->withQueryString()
            )
          ]);
      
    }

    public function editReportInstaller($id, $installation_team)
    {   // Cargar la orden junto con los campos relacionados
       
            $order = Order::with([
                'paymentExtraFields' => function($query) use ($installation_team) {
                    $query->where('installation_team_id', $installation_team);

                }, // Cargar los paymentExtraFields
                'user', // Cargar el usuario relacionado
                'installationTeams.user', // Cargar los equipos de instalación y sus usuarios
                'owners',
                'supervisor' // Cargar los propietarios
            ])->findOrFail($id);

            $biweeklys = Biweekly::where('installation_team_id', $installation_team)->get();

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
                 'payment_status'=> [
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

  public function showBiweekly ($id) 
  {  
      $biweeklys = Biweekly::where('installation_team_id', $id)->get();
      
      

      $companyName = InstallationTeam::where('user_id', $id)->value('company_name');
     
     //dd($biweeklys->toArray());
  // Retornar la vista con las órdenes filtradas
    return Inertia::render('Report/ShowBiweekly', [
        'biweeklys' => $biweeklys->toArray(),
        'installer' => User::find($id),
        'companyName' => $companyName,
        'statuses' => [
        MethodOfPayment::ZELLE->value,
        MethodOfPayment::CHECK->value,
          ]
    ]);
  }

  public function createBiweekly($installation_team)
  { 
          // Retornar la vista con los datos
          return Inertia::render('Report/CreateBiweekly', [
             
               'method_payment' => [
                MethodOfPayment::CHECK->value,
                MethodOfPayment::ZELLE->value,
               ],
              'installation_team_id' => $installation_team,
          ]);
  }

  public function  storeBiweekly(Request $request, CreateBiweekly $createBiweekly)
  {
    //dd($request);
      $createBiweekly->handle($request);
      return redirect()->route('report.show_biweekly', $request->input('installation_team_id'))
      ->with('success', 'Order updated successfully.');
  }

  public function editBiweekly($id, $installation_team)
  {   // Cargar la orden junto con los campos relacionados
     
          $biweekly = Biweekly::findOrFail($id);
          $period [] = $biweekly->start_biweekly_period;
          $period [] = $biweekly->end_biweekly_period;
          //dd( $period );
          // Retornar la vista con los datos
          return Inertia::render('Report/EditBiweekly', [
              'biweekly' => $biweekly,
              'installation_team_id' => $installation_team,
              'period' => $period,
              'method_payment' => [
                MethodOfPayment::CHECK->value,
                MethodOfPayment::ZELLE->value,
               ],
          ]);
  }

   public function updateBiweekly(Request $request, UpdateBiweekly $updateBiweekly)
  {
    //dd($request);
    $biweekly = Biweekly::findOrFail($request->input('id'));
    $updateBiweekly->handle($request, $biweekly);
      return redirect()->route('report.show_biweekly', $request->input('installation_team_id'))
      ->with('success', 'Order updated successfully.');
  }

  public function exportPaymentInstaller(Request $request, $id) 
{      $biweeklystar = Carbon::parse( Biweekly::where('id', $id)->value('start_biweekly_period'))->format('d F Y');
       $biweeklyend = Carbon::parse( Biweekly::where('id', $id)->value('end_biweekly_period'))->format('d F Y');
      
        return Excel::download( 
          new InstallerExport($id), 
          'Biweekly '. $biweeklystar . ' to ' . $biweeklyend. '.xlsx', 
          \Maatwebsite\Excel\Excel::XLSX
        );
    }


  
     


   

    /*public function storeContact(Request $request)
    {
      $contact = [
        'Last_Name' => 'Losada',
        'Email' => 'losada24@gmail.com',
        'Mobile' => '2397632058',
        'Description' => 'This user is generated associate program',
        'Source' => 'Employee Referral',
      ];

      $result = $this->createContact($contact);
      dd($result);
    } */
}
