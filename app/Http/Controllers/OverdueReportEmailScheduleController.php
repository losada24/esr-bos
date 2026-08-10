<?php

namespace App\Http\Controllers;

use App\Enum\StatusUserEnum;
use App\Models\OverdueReportEmailSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OverdueReportEmailScheduleController extends Controller
{
  private const DAYS = [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday',
  ];

  public function edit(): Response
  {
    $schedule = OverdueReportEmailSchedule::current();
    $users = User::query()
      ->select('id', 'name', 'email')
      ->where('status', StatusUserEnum::ACTIVE->value)
      ->whereNotNull('email')
      ->where('email', '!=', '')
      ->orderBy('name')
      ->get();

    return Inertia::render('Administration/OverdueReportEmailSchedule', [
      'schedule' => [
        'enabled' => (bool) $schedule->enabled,
        'send_time' => substr((string) $schedule->send_time, 0, 5),
        'timezone' => $schedule->timezone ?: 'America/New_York',
        'days_of_week' => $schedule->days_of_week ?? [],
        'user_recipient_ids' => $schedule->user_recipient_ids ?? [],
        'manual_recipients' => $schedule->manual_recipients ?? [],
        'last_sent_at' => $schedule->last_sent_at?->toDateTimeString(),
      ],
      'users' => $users,
      'days' => self::DAYS,
    ]);
  }

  public function update(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'enabled' => ['required', 'boolean'],
      'send_time' => ['required', 'date_format:H:i'],
      'days_of_week' => ['array'],
      'days_of_week.*' => ['string', Rule::in(self::DAYS)],
      'user_recipient_ids' => ['array'],
      'user_recipient_ids.*' => ['integer', 'exists:users,id'],
      'manual_recipients_text' => ['nullable', 'string'],
    ]);

    $manualRecipients = $this->parseManualRecipients((string) ($validated['manual_recipients_text'] ?? ''));

    OverdueReportEmailSchedule::current()->update([
      'enabled' => (bool) $validated['enabled'],
      'send_time' => $validated['send_time'],
      'timezone' => 'America/New_York',
      'days_of_week' => array_values($validated['days_of_week'] ?? []),
      'user_recipient_ids' => array_values($validated['user_recipient_ids'] ?? []),
      'manual_recipients' => $manualRecipients,
    ]);

    return redirect()
      ->route('administration.overdue-report-email-schedule.edit')
      ->with('success', 'Overdue report email schedule saved successfully.');
  }

  private function parseManualRecipients(string $value): array
  {
    return collect(preg_split('/[\s,;]+/', $value) ?: [])
      ->map(fn (string $email) => trim($email))
      ->filter(fn (string $email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
      ->unique(fn (string $email) => mb_strtolower($email))
      ->values()
      ->all();
  }
}
