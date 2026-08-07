<?php

namespace App\Console\Commands;

use App\Jobs\SendGmailEmail;
use App\Mail\OverdueStageOrdersScheduledReport;
use App\Models\OverdueReportEmailSchedule;
use App\Models\User;
use App\Services\OverdueStageOrdersReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendOverdueStageOrdersReport extends Command
{
    protected $signature = 'app:send-overdue-stage-orders-report {--force : Send even when it is not the configured day/time}';

    protected $description = 'Send the scheduled overdue stage orders report.';

    public function handle(OverdueStageOrdersReportService $reportService): int
    {
        $schedule = OverdueReportEmailSchedule::query()->first();

        if (! $schedule || (! $schedule->enabled && ! $this->option('force'))) {
            $this->info('Overdue report email schedule is disabled.');
            return self::SUCCESS;
        }

        $now = Carbon::now($schedule->timezone ?: OverdueReportEmailSchedule::defaultTimezone());

        if (! $this->option('force') && ! $this->shouldSendToday($schedule, $now)) {
            $this->info('Overdue report email schedule is not due.');
            return self::SUCCESS;
        }

        $recipients = $this->recipients($schedule);

        if ($recipients === []) {
            $this->warn('No recipients configured for overdue report email schedule.');
            return self::SUCCESS;
        }

        $data = $reportService->buildForScheduledEmail();

        SendGmailEmail::dispatch($recipients, new OverdueStageOrdersScheduledReport($data))->onQueue('emails');

        $schedule->update([
            'last_sent_at' => Carbon::now(),
            'last_sent_date' => $now->toDateString(),
        ]);

        $this->info('Overdue report email was queued.');

        return self::SUCCESS;
    }

    private function shouldSendToday(OverdueReportEmailSchedule $schedule, Carbon $now): bool
    {
        $weekdays = collect($schedule->weekdays ?? [])->map(fn (string $day) => strtolower($day))->all();

        if (! in_array(strtolower($now->englishDayOfWeek), $weekdays, true)) {
            return false;
        }

        if ($schedule->last_sent_date?->toDateString() === $now->toDateString()) {
            return false;
        }

        [$hour, $minute] = array_map('intval', explode(':', (string) $schedule->send_time));
        $sendAt = $now->copy()->setTime($hour, $minute);

        return $now->greaterThanOrEqualTo($sendAt);
    }

    private function recipients(OverdueReportEmailSchedule $schedule): array
    {
        $userEmails = User::query()
            ->whereIn('id', $schedule->recipient_user_ids ?? [])
            ->pluck('email')
            ->all();

        return collect($userEmails)
            ->merge($schedule->manual_emails ?? [])
            ->filter(fn ($email) => is_string($email) && trim($email) !== '')
            ->map(fn (string $email) => trim($email))
            ->unique(fn (string $email) => strtolower($email))
            ->values()
            ->all();
    }
}
