<?php

namespace App\Support;

use App\Models\CrmEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CrmEventRecurrence
{
    public function occurrencesBetween(CrmEvent $event, Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        if (!$this->isRecurring($event) || !$event->starts_at || !$event->ends_at) {
            return collect();
        }

        $timezone = (string) config('app.timezone', 'UTC');
        $seriesStart = Carbon::parse($event->starts_at, $timezone);
        $durationSeconds = max(60, Carbon::parse($event->ends_at, $timezone)->diffInSeconds($seriesStart));
        $seriesEnd = $event->recurrence_ends_at
            ? Carbon::parse($event->recurrence_ends_at, $timezone)->endOfDay()
            : null;
        $effectiveEnd = $seriesEnd && $seriesEnd->lt($rangeEnd) ? $seriesEnd : $rangeEnd;

        if ($effectiveEnd->lt($seriesStart) || $rangeEnd->lt($seriesStart)) {
            return collect();
        }

        return match ($event->recurrence_frequency) {
            'weekly', 'biweekly' => $this->weeklyOccurrences($event, $seriesStart, $durationSeconds, $rangeStart, $effectiveEnd),
            'monthly' => $this->monthlyOccurrences($event, $seriesStart, $durationSeconds, $rangeStart, $effectiveEnd),
            default => collect(),
        };
    }

    public function nextOccurrenceDueForReminder(CrmEvent $event, Carbon $now): ?array
    {
        if (!$this->isRecurring($event) || !$event->reminder_enabled || $event->reminder_minutes_before === null) {
            return null;
        }

        $lookAhead = $now->copy()->addMinutes((int) $event->reminder_minutes_before)->addMinutes(15);

        return $this->occurrencesBetween($event, $now->copy()->subDay(), $lookAhead)
            ->first(function (array $occurrence) use ($event, $now) {
                $dueAt = $occurrence['starts_at']->copy()->subMinutes((int) $event->reminder_minutes_before);

                return $dueAt->lte($now) && $occurrence['ends_at']->gt($now);
            });
    }

    private function weeklyOccurrences(CrmEvent $event, Carbon $seriesStart, int $durationSeconds, Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        $intervalWeeks = $event->recurrence_frequency === 'biweekly'
            ? 2
            : max(1, (int) ($event->recurrence_interval ?? 1));
        $weekday = $event->recurrence_weekday ?? $seriesStart->dayOfWeek;
        $cursor = $seriesStart->copy()->startOfWeek(Carbon::SUNDAY)->addDays($weekday)->setTimeFrom($seriesStart);

        while ($cursor->lt($seriesStart)) {
            $cursor->addWeeks($intervalWeeks);
        }

        while ($cursor->copy()->addSeconds($durationSeconds)->lt($rangeStart)) {
            $cursor->addWeeks($intervalWeeks);
        }

        $occurrences = collect();

        while ($cursor->lte($rangeEnd)) {
            $occurrences->push($this->occurrence($cursor, $durationSeconds));
            $cursor = $cursor->copy()->addWeeks($intervalWeeks);
        }

        return $occurrences;
    }

    private function monthlyOccurrences(CrmEvent $event, Carbon $seriesStart, int $durationSeconds, Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        $intervalMonths = max(1, (int) ($event->recurrence_interval ?? 1));
        $monthDay = $event->recurrence_month_day ?? $seriesStart->day;
        $cursor = $seriesStart->copy()->startOfMonth()->setTimeFrom($seriesStart);
        $occurrences = collect();

        while ($cursor->lte($rangeEnd)) {
            $day = min($monthDay, $cursor->daysInMonth);
            $startsAt = $cursor->copy()->day($day)->setTimeFrom($seriesStart);
            $endsAt = $startsAt->copy()->addSeconds($durationSeconds);

            if ($startsAt->gte($seriesStart) && $endsAt->gte($rangeStart) && $startsAt->lte($rangeEnd)) {
                $occurrences->push(['starts_at' => $startsAt, 'ends_at' => $endsAt]);
            }

            $cursor->addMonthsNoOverflow($intervalMonths)->startOfMonth();
        }

        return $occurrences;
    }

    private function occurrence(Carbon $startsAt, int $durationSeconds): array
    {
        return [
            'starts_at' => $startsAt->copy(),
            'ends_at' => $startsAt->copy()->addSeconds($durationSeconds),
        ];
    }

    private function isRecurring(CrmEvent $event): bool
    {
        return (bool) $event->is_repeating && in_array($event->recurrence_frequency, ['weekly', 'biweekly', 'monthly'], true);
    }
}
