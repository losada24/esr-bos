<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Models\InstallationPayment;

class UpdatePaymentInstaller {

  public function handle(Request $request) {

    DB::transaction(function() use ($request) {
      
      $data = [
        'order_id' => $request->order_id,
        'installation_team_id' => $request->installation_team_id,
        'installer_payment' => $request->installer_payment,
        'percentage_payment' => $request->percentage_payment,
        'payment_date' => $request->payment_date,
      ];

      if ($request->id == 0) {
        InstallationPayment::create($data);
    } else {
        // Si el id existe, se actualiza el registro
        InstallationPayment::updateOrCreate(
            ['id' => $request->id], // Condición para buscar el registro por ID
            $data // Si existe, actualiza con estos datos
        );
      } 
    });
  }
}
