<?php

use App\Support\PaymentInstallmentAccounting;

test('returns pending when nothing has been paid', function () {
    $summary = PaymentInstallmentAccounting::summarize(400, 0);

    expect($summary['status'])->toBe('PENDING')
        ->and($summary['paid_amount'])->toBe(0.0)
        ->and($summary['balance'])->toBe(400.0)
        ->and($summary['credit'])->toBe(0.0);
});

test('returns partial when payment is below schedule', function () {
    $summary = PaymentInstallmentAccounting::summarize(400, 250);

    expect($summary['status'])->toBe('PARTIAL')
        ->and($summary['balance'])->toBe(150.0)
        ->and($summary['credit'])->toBe(0.0);
});

test('returns paid when payment matches schedule', function () {
    $summary = PaymentInstallmentAccounting::summarize(400, 400);

    expect($summary['status'])->toBe('PAID')
        ->and($summary['balance'])->toBe(0.0)
        ->and($summary['credit'])->toBe(0.0);
});

test('returns overpaid and credit when payment is above schedule', function () {
    $summary = PaymentInstallmentAccounting::summarize(400, 500);

    expect($summary['status'])->toBe('OVERPAID')
        ->and($summary['balance'])->toBe(0.0)
        ->and($summary['credit'])->toBe(100.0);
});
