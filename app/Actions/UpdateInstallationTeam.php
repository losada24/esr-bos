<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enum\RoleEnum;
use App\Models\Attachment;
use App\Models\InstallationTeam;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class UpdateInstallationTeam {

  public function handle(Request $request, InstallationTeam $installationTeam) {

    DB::transaction(function() use ($request, $installationTeam) {

      if( !$installationTeam )
      {
          throw new \Exception('Not not updated');
      }

      $workerCompensation = $installationTeam->attachments()->where('file_type', 'worker_compensation_attach')->first();
      $workerCompensationAttachPath = $workerCompensation->file_path;
      $workerCompensationFileName = $workerCompensation->filename;
      if ($request->hasFile('worker_compensation_attach')) {
        $oldWorkerCompensationAttachPath = $workerCompensationAttachPath;
        $workerCompensationFileName = time() . '_' . $request->file('worker_compensation_attach')->getClientOriginalName();
        $workerCompensationAttachPath = $request->file('worker_compensation_attach')->storeAs('installation_team_files', $workerCompensationFileName, 'public');
        if ($workerCompensationAttachPath && $oldWorkerCompensationAttachPath) {
          Storage::disk('public')->delete($oldWorkerCompensationAttachPath);
        }
      }

      $workerCompensationException = $installationTeam->attachments()->where('file_type', 'worker_compensation_exception_attach')->first();
      $workerCompensationExceptionFileName = null;
      $workerCompensationExceptionAttachPath = null;
      $oldWorkerCompensationExceptionAttachPath = null;
      if ($workerCompensationException) {
        $workerCompensationExceptionAttachPath = $workerCompensationException->file_path;
        $workerCompensationExceptionFileName = $workerCompensationException->filename;
      }
      if ($request->hasFile('worker_compensation_exception_attach')) {
        $oldWorkerCompensationExceptionAttachPath = $workerCompensationExceptionAttachPath;
        $workerCompensationExceptionFileName = time() . '_' . $request->file('worker_compensation_exception_attach')->getClientOriginalName();
        $workerCompensationExceptionAttachPath = $request->file('worker_compensation_exception_attach')->storeAs('installation_team_files', $workerCompensationExceptionFileName, 'public');
        if ($workerCompensationExceptionAttachPath && $oldWorkerCompensationExceptionAttachPath) {
          Storage::disk('public')->delete($oldWorkerCompensationExceptionAttachPath);
        }
      }
      if (!$workerCompensationException && $request->hasFile('worker_compensation_exception_attach')) {
        $installationTeam->attachments()->saveMany([
          new Attachment(
            [
              'filename' => $workerCompensationExceptionFileName,
              'file_path' => $workerCompensationExceptionAttachPath,
              'file_type' => 'worker_compensation_exception_attach',
            ]),
        ]);
      }
      
      $liabilityExpiration = $installationTeam->attachments()->where('file_type', 'liability_expiration_attach')->first();
      $liabilityExpirationAttachPath = $liabilityExpiration->file_path;
      $liabilityExpirationFileName = $liabilityExpiration->filename;
      if ($request->hasFile('liability_expiration_attach')) {
        $oldLiabilityExpirationAttachPath = $liabilityExpirationAttachPath;
        $liabilityExpirationFileName = time() . '_' . $request->file('liability_expiration_attach')->getClientOriginalName();
        $liabilityExpirationAttachPath = $request->file('liability_expiration_attach')->storeAs('installation_team_files', $liabilityExpirationFileName, 'public');
        if ($liabilityExpirationAttachPath && $oldLiabilityExpirationAttachPath) {
          Storage::disk('public')->delete($oldLiabilityExpirationAttachPath);
        }
      }

      $installerAgrement = $installationTeam->attachments()->where('file_type', 'installer_agrement_attach')->first();

      $installerAgrementFileName = null;
      $installerAgrementAttachPath = null;
      $oldInstallerAgrementAttachPath = null;
      if ($installerAgrement) {
        $installerAgrementAttachPath = $installerAgrement->file_path;
        $installerAgrementFileName = $installerAgrement->filename;
      }
      if ($request->hasFile('installer_agrement_attach')) {
        $oldInstallerAgrementAttachPath = $installerAgrementAttachPath;
        $installerAgrementFileName = time() . '_' . $request->file('installer_agrement_attach')->getClientOriginalName();
        $installerAgrementAttachPath = $request->file('installer_agrement_attach')->storeAs('installation_team_files', $installerAgrementFileName, 'public');
        if ( $installerAgrementAttachPath && $oldInstallerAgrementAttachPath) {
          Storage::disk('public')->delete($oldInstallerAgrementAttachPath);
        }
      }
      if ( !$installerAgrement){
        $installationTeam->attachments()->saveMany([
          
            new Attachment(
              [
                'filename' => $installerAgrementFileName,
                'file_path' => $installerAgrementAttachPath,
                'file_type' => 'installer_agrement_attach',
              ]),
        ]);

      }
    

      
      $annualW9 = $installationTeam->attachments()->where('file_type', 'annual_w9_attach')->first();
      $annualW9FileName = null;
      $annualW9AttachPath = null;
      $oldAnnualW9AttachPath = null;
      if ($annualW9) {
        $annualW9AttachPath = $annualW9->file_path;
        $annualW9FileName = $annualW9->filename;
      }
     
      if ($request->hasFile('annual_w9_attach')) {
        $oldAnnualW9AttachPath = $annualW9AttachPath;
        $annualW9FileName = time() . '_' . $request->file('annual_w9_attach')->getClientOriginalName();
        $annualW9AttachPath = $request->file('annual_w9_attach')->storeAs('installation_team_files', $annualW9FileName, 'public');
        if ($annualW9AttachPath && $oldAnnualW9AttachPath) {
          Storage::disk('public')->delete($oldAnnualW9AttachPath);
        }
      }
      if ( !$annualW9){
        $installationTeam->attachments()->saveMany([
          
            new Attachment(
              [
                'filename' => $annualW9FileName,
                'file_path' => $annualW9AttachPath,
                'file_type' => 'annual_w9_attach',
              ]),
        ]);

      }

      //dd($annualW9FileName,$annualW9AttachPath);

      $installationTeamData = [
        'user_id' => $request->user_id,
        'number_of_member' => $request->number_of_member,
        'worker_compensation_expiration_date' => $request->worker_compensation_expiration_date,
        'liability_expiration_date' => $request->liability_expiration_date,
        'annual_w9_expiration_date' => $request->annual_w9_expiration_date,
        'company_name' => $request->company_name,
        'phone' => $request->phone,
      ];

      $installationTeam->update($installationTeamData);
      $installationTeam->typeHousing()->sync($request->type_of_housings);
      $installationTeam->travelCost()->sync($request->travel_costs);
      $attachments = $installationTeam->attachments()->get();
      //dd($attachments,$annualW9FileName,$annualW9AttachPath);
      foreach ($attachments as $attachment) {
        if ($attachment->file_type == 'worker_compensation_attach') {
          $attachment->update([
            'filename' => $workerCompensationFileName,
            'file_path' => $workerCompensationAttachPath,
          ]);
        }
        else if ($attachment->file_type == 'worker_compensation_exception_attach') {
          $attachment->update([
            'filename' => $workerCompensationExceptionFileName,
            'file_path' => $workerCompensationExceptionAttachPath,
          ]);
        }
        else if ($attachment->file_type == 'installer_agrement_attach') {
          $attachment->update([
            'filename' => $installerAgrementFileName,
            'file_path' => $installerAgrementAttachPath,
          ]);
        }
        else if ($attachment->file_type == 'annual_w9_attach') {
          $attachment->update([
            'filename' => $annualW9FileName,
            'file_path' => $annualW9AttachPath,
          ]);
        }
        
        else {
          $attachment->update([
            'filename' => $liabilityExpirationFileName,
            'file_path' => $liabilityExpirationAttachPath,
          ]);
        }
      }
      $installationTeam->attachments()->saveMany($attachments);
    });
  }
}
