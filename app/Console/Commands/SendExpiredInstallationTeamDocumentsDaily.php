<?php

namespace App\Console\Commands;

use App\Enum\RoleEnum;
use App\Jobs\SendGmailEmail;
use App\Mail\InstallationTeamExpireDocuments;
use App\Models\InstallationTeam;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendExpiredInstallationTeamDocumentsDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-expired-installation-team-documents-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send expired installation team documents daily.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $installationTeams = InstallationTeam::with('user')
          ->where('worker_compensation_expiration_date', '<=', Carbon::now()->addMonth(1))
          ->where('disable_expiration_document_emails', false)
          ->whereHas('user', function ($query) {
            $query->where('status', 'ACTIVE');
          })
          ->get();

          $users = [];
          $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
          $users = array_merge($users, $accountManager->pluck('email')->toArray());
          $extra_emails = explode(',', config('custom.installation_team_expiration_documents_email'));
          $users = array_merge($users, $extra_emails);

        foreach ($installationTeams as $installationTeam) {
          $email_user = array_merge($users, [$installationTeam->user->email]);
          // Mail::to($email_user)->send(new InstallationTeamExpireDocuments($installationTeam, false, true));
          $installationTeamExpireDocuments = new InstallationTeamExpireDocuments($installationTeam, false, true);
          SendGmailEmail::dispatch($email_user, $installationTeamExpireDocuments)->onQueue('emails');
        }
    }
}
