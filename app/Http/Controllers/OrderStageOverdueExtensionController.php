<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStageOverdue;
use App\Models\OrderStageOverdueExtension;
use App\Support\OrderStageOverdueTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderStageOverdueExtensionController extends Controller
{
    public function store(Request $request, Order $order, OrderStageOverdueTracker $tracker): RedirectResponse
    {
        $validated = $request->validate([
            'business_days' => ['required', 'integer', 'min:1', 'max:365'],
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $order->loadMissing('orderStatus');
        $stageAge = $tracker->sync($order);

        if (!($stageAge['overdue'] ?? false)) {
            return back()->withErrors([
                'business_days' => 'This order is not currently overdue.',
            ]);
        }

        $overdue = OrderStageOverdue::query()
            ->where('order_id', $order->id)
            ->where('status', $stageAge['status'])
            ->where('stage_started_at', $stageAge['stage_started_at_raw'])
            ->where('is_active', true)
            ->whereNull('resolved_at')
            ->latest('id')
            ->first();

        if (!$overdue) {
            return back()->withErrors([
                'business_days' => 'The active overdue record could not be found.',
            ]);
        }

        $extendedUntil = now()
            ->copy()
            ->addWeekdays((int) $validated['business_days'])
            ->endOfDay();

        $extension = OrderStageOverdueExtension::query()->create([
            'order_id' => $order->id,
            'order_stage_overdue_id' => $overdue->id,
            'user_id' => $request->user()?->id,
            'status' => (string) $stageAge['status'],
            'stage_started_at' => $stageAge['stage_started_at_raw'],
            'business_days' => (int) $validated['business_days'],
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

        return back()->with('success', 'Overdue extension saved successfully.');
    }
}
