<?php

namespace App\Console\Commands;

use App\Exports\OverdueStageOrdersExport;
use App\Http\Controllers\ReportController;
use App\Jobs\SendGmailEmail;
use App\Mail\OverdueStageOrdersReportMail;
use App\Models\OverdueReportEmailSchedule;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SendOverdueStageOrdersReport extends Command
{
  protected $signature = 'reports:send-overdue-stage-orders {--force : Send now regardless of schedule window}';

  protected $description = 'Send the scheduled overdue stage orders report with PDF and Excel attachments.';

  public function handle(): int
  {
    $schedule = OverdueReportEmailSchedule::current();

    if (!$schedule->enabled && !$this->option('force')) {
      $this->info('Overdue report email schedule is disabled.');
      return self::SUCCESS;
    }

    $now = Carbon::now($schedule->timezone ?: 'America/New_York');

    if (!$this->option('force') && !$this->shouldSendNow($schedule, $now)) {
      $this->info('Not in the configured send window.');
      return self::SUCCESS;
    }

    $userRecipients = $this->userRecipients($schedule);
    $manualRecipients = $this->manualRecipients($schedule);
    $recipientCount = collect($userRecipients)
      ->merge($manualRecipients)
      ->unique(fn (string $email) => mb_strtolower($email))
      ->count();

    if ($recipientCount === 0) {
      $this->warn('No recipients configured.');
      return self::SUCCESS;
    }

    $request = Request::create('/report/overdue-stage-orders', 'GET', [
      'overdue_only' => '1',
    ]);
    $data = app(ReportController::class)->buildOverdueStageOrdersData($request);
    $timestamp = $now->format('Ymd-His');
    $pdfPath = "reports/overdue-stage-orders-{$timestamp}.pdf";
    $excelPath = "reports/overdue-stage-orders-{$timestamp}.xlsx";

    Storage::disk('public')->put(
      $pdfPath,
      Pdf::loadView('pdf.overdue-stage-orders', $data)
        ->setPaper('A4', 'portrait')
        ->output()
    );
    Storage::disk('public')->put(
      $excelPath,
      Excel::raw(new OverdueStageOrdersExport($data), \Maatwebsite\Excel\Excel::XLSX)
    );

    if (!empty($userRecipients)) {
      SendGmailEmail::dispatch(
        $userRecipients,
        $this->mailable($data, $pdfPath, $excelPath)
      )->onQueue('emails');
    }

    if (!empty($manualRecipients)) {
      SendGmailEmail::dispatch(
        $manualRecipients,
        $this->mailable($data, $pdfPath, $excelPath),
        allowInactiveUserRecipient: true
      )->onQueue('emails');
    }

    $schedule->update(['last_sent_at' => now()]);
    $this->info('Overdue report email queued for ' . $recipientCount . ' recipient(s).');

    return self::SUCCESS;
  }

  private function shouldSendNow(OverdueReportEmailSchedule $schedule, Carbon $now): bool
  {
    $days = $schedule->days_of_week ?? [];

    if (!in_array(mb_strtolower($now->format('l')), $days, true)) {
      return false;
    }

    $scheduledAt = $now->copy()->setTimeFromTimeString(substr((string) $schedule->send_time, 0, 5));

    if (!$now->betweenIncluded($scheduledAt, $scheduledAt->copy()->addMinutes(9))) {
      return false;
    }

    if (!$schedule->last_sent_at) {
      return true;
    }

    return Carbon::parse($schedule->last_sent_at)
      ->setTimezone($schedule->timezone ?: 'America/New_York')
      ->lt($scheduledAt);
  }

  private function userRecipients(OverdueReportEmailSchedule $schedule): array
  {
    return User::query()
      ->whereIn('id', $schedule->user_recipient_ids ?? [])
      ->pluck('email')
      ->filter(fn ($email) => is_string($email) && trim($email) !== '')
      ->map(fn (string $email) => trim($email))
      ->unique(fn (string $email) => mb_strtolower($email))
      ->values()
      ->all();
  }

  private function manualRecipients(OverdueReportEmailSchedule $schedule): array
  {
    return collect($schedule->manual_recipients ?? [])
      ->filter(fn ($email) => is_string($email) && trim($email) !== '')
      ->map(fn (string $email) => trim($email))
      ->unique(fn (string $email) => mb_strtolower($email))
      ->values()
      ->all();
  }

  private function mailable(array $data, string $pdfPath, string $excelPath): OverdueStageOrdersReportMail
  {
    return new OverdueStageOrdersReportMail(
      (string) $data['generatedAt'],
      collect($data['totals'] ?? [])->toArray(),
      collect($data['groups'] ?? [])->toArray(),
      $pdfPath,
      $excelPath,
    );
  }
}
