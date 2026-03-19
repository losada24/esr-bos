<?php

use App\Enum\ServiceEnum;
use App\Support\UncollectedCustomerPaymentsReportBuilder;
use Illuminate\Support\Collection;

test('excludes service orders from the uncollected customer payments report', function () {
    $report = UncollectedCustomerPaymentsReportBuilder::build(new Collection([
        [
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Installation Order',
                    'partial_payment_installation' => 0,
                    'final_payment_installation' => 0,
                    'installation_payments' => [
                        ['percentage_payment' => 20],
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Service Order',
                    'partial_payment_installation' => 0,
                    'final_payment_installation' => 0,
                    'installation_payments' => [
                        ['percentage_payment' => 20],
                    ],
                ],
            ],
        ],
    ]), collect([
        1 => ServiceEnum::INSTALLATION->value,
        2 => ServiceEnum::SERVICE->value,
    ]));

    expect($report['uncollected'])->toHaveCount(1)
        ->and($report['uncollected']->pluck('name'))->toContain('Installation Order')
        ->and($report['uncollected']->pluck('name'))->not->toContain('Service Order')
        ->and($report['final_payment_pending'])->toHaveCount(0);
});
