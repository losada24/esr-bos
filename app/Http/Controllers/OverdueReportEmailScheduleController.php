<?php

namespace App\Http\Controllers;

use App\Enum\StatusUserEnum;
use App\Models\OverdueReportEmailSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OverdueReportEmailScheduleController extends Controller
{
    private const WEEKDAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function index()
    {
        $schedule = $this->schedule();

        return Inertia::render('Administration/OverdueReportEmailSchedule', [
            'schedule' => [
                'enabled' => $schedule->enabled,
                'weekdays' => $schedule->weekdays ?? OverdueReportEmailSchedule::defaultWeekdays(),
                'send_time' => substr((string) $schedule->send_time, 0, 5),
                'timezone' => $schedule->timezone ?: OverdueReportEmailSchedule::defaultTimezone(),
                'recipient_user_ids' => $schedule->recipient_user_ids ?? [],
                'manual_emails' => implode("\n", $schedule->manual_emails ?? []),
                'last_sent_at' => $schedule->last_sent_at?->toDateTimeString(),
                'last_sent_date' => $schedule->last_sent_date?->toDateString(),
            ],
            'users' => User::query()
                ->where('status', StatusUserEnum::ACTIVE->value)
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            'weekdays' => self::WEEKDAYS,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['required', 'string', Rule::in(self::WEEKDAYS)],
            'send_time' => ['required', 'date_format:H:i'],
            'recipient_user_ids' => ['nullable', 'array'],
            'recipient_user_ids.*' => ['integer', 'exists:users,id'],
            'manual_emails' => ['nullable', 'string'],
        ]);

        $manualEmails = $this->normalizeManualEmails((string) ($validated['manual_emails'] ?? ''));
        $invalidEmails = collect($manualEmails)
            ->reject(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        if ($invalidEmails !== []) {
            return Redirect::back()
                ->withErrors(['manual_emails' => 'Invalid email: ' . implode(', ', $invalidEmails)])
                ->withInput();
        }

        $this->schedule()->update([
            'enabled' => (bool) $validated['enabled'],
            'weekdays' => array_values($validated['weekdays']),
            'send_time' => $validated['send_time'] . ':00',
            'timezone' => OverdueReportEmailSchedule::defaultTimezone(),
            'recipient_user_ids' => array_values($validated['recipient_user_ids'] ?? []),
            'manual_emails' => $manualEmails,
        ]);

        return Redirect::route('overdue-report-email-schedule.index')
            ->with('success', 'Overdue report email schedule updated.');
    }

    private function schedule(): OverdueReportEmailSchedule
    {
        return OverdueReportEmailSchedule::query()->firstOrCreate([], [
            'enabled' => false,
            'weekdays' => OverdueReportEmailSchedule::defaultWeekdays(),
            'send_time' => '08:00:00',
            'timezone' => OverdueReportEmailSchedule::defaultTimezone(),
            'recipient_user_ids' => [],
            'manual_emails' => [],
        ]);
    }

    private function normalizeManualEmails(string $value): array
    {
        return collect(preg_split('/[\s,;]+/', $value) ?: [])
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->unique(fn (string $email) => strtolower($email))
            ->values()
            ->all();
    }
}
