<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enum\RoleEnum;
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

      $installationTeamData = [
        'user_id' => $request->user_id,
        'number_of_member' => $request->number_of_member,
        'worker_compensation_expiration_date' => $request->worker_compensation_expiration_date,
        'liability_expiration_date' => $request->liability_expiration_date,
        'company_name' => $request->company_name,
        'phone' => $request->phone,
      ];

      $installationTeam->update($installationTeamData);
      $installationTeam->typeHousing()->sync($request->type_of_housings);
      $installationTeam->travelCost()->sync($request->travel_costs);
      $attachments = $installationTeam->attachments()->get();
      foreach ($attachments as $attachment) {
        if ($attachment->file_type == 'worker_compensation_attach') {
          $attachment->update([
            'filename' => $workerCompensationFileName,
            'file_path' => $workerCompensationAttachPath,
          ]);
        } else {
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
