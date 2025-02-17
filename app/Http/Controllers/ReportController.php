<?php

namespace App\Http\Controllers;

use App\Actions\UpdatePaymentInstaller;
use App\Enum\InstallerPaymentStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Exports\SupervisorExport;
use App\Http\Requests\StoreInstallerPaymentRequest;
use App\Http\Resources\InstallationTeamCollection;
use App\Models\InstallationPayment;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\PaymentExtraField;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use App\Rules\ValidateInstallationPayment;  
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
    {
          // Obtener las órdenes por supervisor
        $orders = $this->getOrdersByInstaller($id);
        //dd($orders);
        
        $name = request()->get('name');

        $companyName = InstallationTeam::where('user_id', $id)->value('company_name');
       
       //dd($companyName);
        $startDate = request()->get('start_date');
        $endDate = request()->get('end_date');

        // Filtrar las órdenes por estado
       /* if ($orders instanceof EloquentBuilder || $orders instanceof QueryBuilder) {
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

        if ($name) {
          $orders = $orders->filter(function ($order) use ($name) {
              return stripos($order['name'], $name) !== false; // Filtro por nombre
          });
      }
        //dd($orders);

        /*if ($startDate) {
              $orders = $orders->where('supervisor_payment_date', '>=', $startDate);
        }

        if ($endDate) {
              $orders = $orders->where('supervisor_payment_date', '<=', $endDate);
        }*/

        //dd(User::find($id));
      

    // Retornar la vista con las órdenes filtradas
    return Inertia::render('Report/ShowInstaller', [
        'orders' => $orders->values()->toArray(),
        'installer' => User::find($id),
        'companyName' => $companyName,
        'statuses' => [
          SupervisorPaymentStatusEnum::OPEN->value,
          SupervisorPaymentStatusEnum::PENDING->value,
          SupervisorPaymentStatusEnum::CLOSED->value,
          SupervisorPaymentStatusEnum::NO_PAID->value,
          ]
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

            $amount = $order->getGrandTotalPrice();

            $payment = InstallationPayment::where('order_id', $id)->get();


            //dd($payment);
            // Retornar la vista con los datos
            return Inertia::render('Report/EditReportInstaller', [
                'order' => $order, // Pasamos los datos de la orden
                'installation_team_id' => $installation_team,
                'amount' => $amount,
                'payment' => $payment->values()->toArray(),
                 'installer_payment_status' => [
                  InstallerPaymentStatusEnum::OPEN->value,
                  InstallerPaymentStatusEnum::PENDING->value,
                   InstallerPaymentStatusEnum::PARTIALLY_PAID->value,
                   InstallerPaymentStatusEnum::FULLY_PAID->value,
                   InstallerPaymentStatusEnum::CLOSED->value,
                 ]
            ]);
    }
   
    public function  updateInstallerReport(Request $request)
    {   // Cargar la orden junto con los campos relacionados

      $data = [
        'order_id' => $request->input('order_id'),
        'installation_team_id' => $request->input('installation_team_id'),
        'responsible_extra_work' => $request->input('responsible_extra_work'),
        'notes' => $request->input('notes'),
        'documents_submitted' => $request->input('documents_submitted'),
        'collected_payment' => $request->input('collected_payment'),
        'extra_work' => $request->input('extra_work'),
        'extra_discount' => $request->input('extra_discount'),
        'other_cost_installer' => $request->input('other_cost_installer'),
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
