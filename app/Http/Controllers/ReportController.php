<?php

namespace App\Http\Controllers;

use App\Enum\SupervisorPaymentStatusEnum;
use App\Exports\SupervisorExport;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

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
