<?php

use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Models\Order;

test('financed orders require a financing jobsite address at the ten thousand threshold', function () {
    expect(Order::requiresFinancingJobsiteAddress(
        OrderStatusEnum::ACCOUNT_RECEIPT->value,
        MethodOfPayment::FINANCED->value,
        10000
    ))->toBeTrue();

    expect(Order::requiresFinancingJobsiteAddress(
        OrderStatusEnum::ACCOUNT_RECEIPT->value,
        MethodOfPayment::FINANCED->value,
        9999.99
    ))->toBeFalse();
});

test('cash and financed orders do not use the financed-only requirement', function () {
    expect(Order::requiresFinancingJobsiteAddress(
        OrderStatusEnum::ACCOUNT_RECEIPT->value,
        MethodOfPayment::FINANCEDCASH->value,
        15000
    ))->toBeFalse();
});

test('the requirement only applies when entering account receipt', function () {
    expect(Order::requiresFinancingJobsiteAddress(
        OrderStatusEnum::REVIEW->value,
        MethodOfPayment::FINANCED->value,
        10000
    ))->toBeFalse();
});
