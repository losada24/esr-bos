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
      $installerAgrementAttachPath = null;
      $installerAgrementFileName = null;
      if ($request->hasFile('installer_agrement_attach')) {
        $installerAgrementFileName = time() . '_' . $request->file('installer_agrement_attach')->getClientOriginalName();
        $installerAgrementAttachPath = $request->file('installer_agrement_attach')->storeAs('installation_team_files',  $installerAgrementFileName, 'public');
      }
      $annualW9AttachPath = null;
      $annualW9FileName = null;
      if ($request->hasFile('annual_w9_attach')) {
        $annualW9FileName = time() . '_' . $request->file('annual_w9_attach')->getClientOriginalName();
        $annualW9AttachPath = $request->file('annual_w9_attach')->storeAs('installation_team_files',  $annualW9FileName, 'public');
      }
      
      $installationTeam = InstallationTeam::create([
        'user_id' => $request->user_id,
        'number_of_member' => $request->number_of_member,
        'worker_compensation_expiration_date' => $request->worker_compensation_expiration_date,
        'liability_expiration_date' => $request->liability_expiration_date,
        'annual_w9_expiration_date' => $request->annual_w9_expiration_date,
        'company_name' => $request->company_name,
        'phone' => $request->phone,
      ]);

      $installationTeam->typeHousing()->attach($request->type_of_housings);
      $installationTeam->travelCost()->attach($request->travel_costs);
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
          ]),
          new Attachment(
            [
              'filename' =>  $installerAgrementFileName,
              'file_path' => $installerAgrementAttachPath,
              'file_type' => 'installer_agrement_attach',
            ]),
          new Attachment(
            [
              'filename' => $annualW9FileName,
              'file_path' => $annualW9AttachPath,
              'file_type' => 'annual_w9_attach',
            ]),
      ]);
      
      if( !$installationTeam )
      {
          throw new \Exception('Installation team not created');
      }

    });
  }
}
