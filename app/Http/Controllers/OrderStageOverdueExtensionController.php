<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStageOverdue;
use App\Models\OrderStageOverdueExtension;
use App\Support\OrderStageOverdueTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStageOverdueExtensionController extends Controller
{
    public function store(Request $request, Order $order, OrderStageOverdueTracker $tracker): RedirectResponse
    {
        $validated = $request->validate([
            'business_days' => ['required', 'integer', 'min:1', 'max:'.OrderStageOverdueExtension::MAX_CUMULATIVE_BUSINESS_DAYS],
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $order->loadMissing('orderStatus');
        $stageAge = $tracker->sync($order);

        if (! ($stageAge['overdue'] ?? false)) {
            return back()->withErrors([
                'business_days' => 'This order is not currently overdue.',
            ]);
        }

        DB::transaction(function () use ($order, $request, $stageAge, $validated): void {
            $overdue = OrderStageOverdue::query()
                ->where('order_id', $order->id)
                ->where('status', $stageAge['status'])
                ->where('stage_started_at', $stageAge['stage_started_at_raw'])
                ->where('is_active', true)
                ->whereNull('resolved_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $overdue) {
                throw ValidationException::withMessages([
                    'business_days' => 'The active overdue record could not be found.',
                ]);
            }

            $usedBusinessDays = (int) $overdue->extensions()->sum('business_days');
            $remainingBusinessDays = max(
                0,
                OrderStageOverdueExtension::MAX_CUMULATIVE_BUSINESS_DAYS - $usedBusinessDays
            );
            $requestedBusinessDays = (int) $validated['business_days'];

            if ($requestedBusinessDays > $remainingBusinessDays) {
                throw ValidationException::withMessages([
                    'business_days' => $remainingBusinessDays > 0
                        ? "Only {$remainingBusinessDays} business days remain for this status overdue."
                        : 'The 30 business day extension limit has been reached for this status overdue.',
                ]);
            }

            $latestExtension = $overdue->extensions()->latest('id')->first();
            $extensionStartsAt = $latestExtension?->extended_until?->isFuture()
                ? $latestExtension->extended_until->copy()
                : now();
            $extendedUntil = $extensionStartsAt
                ->addWeekdays($requestedBusinessDays)
                ->endOfDay();

            $extension = OrderStageOverdueExtension::query()->create([
                'order_id' => $order->id,
                'order_stage_overdue_id' => $overdue->id,
                'user_id' => $request->user()?->id,
                'status' => (string) $stageAge['status'],
                'stage_started_at' => $stageAge['stage_started_at_raw'],
                'business_days' => $requestedBusinessDays,
                'extended_until' => $extendedUntil,
                'note' => trim((string) $validated['note']),
            ]);

            $order->notes()->create([
                'content' => sprintf(
                    'Overdue extended by %d business days until %s. Note: %s',
                    (int) $extension->business_days,
                    $extension->extended_until?->format('m/d/Y') ?? '-',
                    $extension->note
                ),
                'type' => 'overdue_extension',
                'user_id' => $request->user()?->id,
            ]);
        });

        return back()->with('success', 'Overdue extension saved successfully.');
    }
}
