<?php
namespace App\Actions;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\InstallationTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreateInstallationTeam {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {
      
      $workerCompensationAttachPath = null;
      $workerCompensationFileName = null;
      if ($request->hasFile('worker_compensation_attach')) {
        $workerCompensationFileName = time() . '_' . $request->file('worker_compensation_attach')->getClientOriginalName();
        $workerCompensationAttachPath = $request->file('worker_compensation_attach')->storeAs('installation_team_files', $workerCompensationFileName, 'public');
      }
      
      $liabilityExpirationAttachPath = null;
      $liabilityExpirationFileName = null;
      if ($request->hasFile('liability_expiration_attach')) {
        $liabilityExpirationFileName = time() . '_' . $request->file('liability_expiration_attach')->getClientOriginalName();
        $liabilityExpirationAttachPath = $request->file('liability_expiration_attach')->storeAs('installation_team_files', $liabilityExpirationFileName, 'public');
      }
      
      $installationTeam = InstallationTeam::create([
        'user_id' => $request->user_id,
        'number_of_member' => $request->number_of_member,
        'worker_compensation_expiration_date' => $request->worker_compensation_expiration_date,
        'liability_expiration_date' => $request->liability_expiration_date,
      ]);

      $installationTeam->typeHousing()->attach($request->type_of_housings);
      $installationTeam->attachments()->saveMany([
        new Attachment(
          [
            'filename' => $workerCompensationFileName,
            'file_path' => $workerCompensationAttachPath,
            'file_type' => 'worker_compensation_attach',
          ]),
        new Attachment(
          [ 
            'filename' => $liabilityExpirationFileName,
            'file_path' => $liabilityExpirationAttachPath,
            'file_type' => 'liability_expiration_attach',
          ])
      ]);
      
      if( !$installationTeam )
      {
          throw new \Exception('Installation team not created');
      }

    });
  }
}
