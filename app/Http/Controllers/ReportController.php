<?php

namespace App\Http\Controllers;

use App\Exports\SupervisorExport;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

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
      $orders = $this->getOrdersBySupervisor($id);
      //dd($orders);
      return Inertia::render('Report/ShowSupervisor', [
        'orders' => $orders,
        'supervisor' => User::find($id),
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
