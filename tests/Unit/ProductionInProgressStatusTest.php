<?php

use App\Enum\OrderStatusEnum;
use App\Http\Controllers\EsrProcessController;
use App\Support\OrderStageOverdueTracker;
use Tests\TestCase;

uses(TestCase::class);

test('production in progress follows pending glass invoice in the ESR pipeline', function () {
    $controller = new class extends EsrProcessController
    {
        public function statuses(): array
        {
            return $this->storageStatuses();
        }
    };

    $statuses = $controller->statuses();
    $pendingGlassInvoiceIndex = array_search(OrderStatusEnum::PENDING_GLASS_INVOICE->value, $statuses, true);

    expect($pendingGlassInvoiceIndex)->not->toBeFalse()
        ->and($statuses[$pendingGlassInvoiceIndex + 1] ?? null)
        ->toBe(OrderStatusEnum::PRODUCTION_IN_PROGRESS->value);
});

test('production in progress has the same due time as pending glass invoice', function () {
    $limits = app(OrderStageOverdueTracker::class)->stageBusinessDayLimits();

    expect($limits[OrderStatusEnum::PRODUCTION_IN_PROGRESS->value])
        ->toBe($limits[OrderStatusEnum::PENDING_GLASS_INVOICE->value])
        ->toBe(7);
});
