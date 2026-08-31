<?php

use Tests\TestCase;

uses(TestCase::class);

function overdueStageReportFixture(): array
{
    return [
        'generatedAt' => '2026-08-31 10:30:00',
        'selectedSellerName' => 'All sellers',
        'totals' => [
            'statuses' => 1,
            'configured_statuses' => 1,
            'orders' => 3,
            'overdue_orders' => 2,
            'overdue_extended_orders' => 1,
            'overdue_amount' => 3000,
            'overdue_extended_amount' => 1500,
            'amount' => 4500,
        ],
        'groups' => [[
            'status' => 'PRODUCTION',
            'threshold_label' => '25 business days',
            'note' => 'Overdue after more than 25 business days in PRODUCTION.',
            'is_configured' => true,
            'overdue_count' => 2,
            'overdue_extended_count' => 1,
            'overdue_amount' => 3000,
            'overdue_extended_amount' => 1500,
            'amount' => 4500,
            'count' => 3,
            'seller_groups' => [[
                'label' => 'Test Seller',
                'source' => 'seller',
                'count' => 3,
                'rows' => [
                    [
                        'id' => 101,
                        'status' => 'PRODUCTION',
                        'order_label' => '#101 - Red order',
                        'amount' => 1000,
                        'days_in_stage' => 30,
                        'order_type' => 'Residential',
                        'product_line' => 'ESR',
                        'stage_entered_at' => '2026-07-20 08:00:00',
                        'is_overdue' => true,
                        'overdue_extension_active' => false,
                        'overdue_extension' => null,
                    ],
                    [
                        'id' => 102,
                        'status' => 'PRODUCTION',
                        'order_label' => '#102 - Yellow extended order',
                        'amount' => 1500,
                        'days_in_stage' => 32,
                        'order_type' => 'Commercial',
                        'product_line' => 'ESR',
                        'stage_entered_at' => '2026-07-16 08:00:00',
                        'is_overdue' => true,
                        'overdue_extension_active' => true,
                        'overdue_extension' => [
                            'business_days' => 5,
                            'extended_until' => '2026-09-04T23:59:59-04:00',
                            'note' => 'Approved extension',
                            'user' => ['name' => 'Test Manager'],
                        ],
                    ],
                    [
                        'id' => 103,
                        'status' => 'PRODUCTION',
                        'order_label' => '#103 - Second red order',
                        'amount' => 2000,
                        'days_in_stage' => 28,
                        'order_type' => 'Residential',
                        'product_line' => 'ESR',
                        'stage_entered_at' => '2026-07-22 08:00:00',
                        'is_overdue' => true,
                        'overdue_extension_active' => false,
                        'overdue_extension' => null,
                    ],
                ],
            ]],
        ]],
    ];
}

test('pdf separates overdue and actively extended orders with distinct colors', function () {
    $html = view('pdf.overdue-stage-orders', overdueStageReportFixture())->render();

    expect($html)
        ->toContain('Overdue Orders')
        ->toContain('Overdue Extended')
        ->toContain('Overdue Amount')
        ->toContain('Overdue Extended Amount')
        ->toContain('Total Amount')
        ->toContain('$3,000.00')
        ->toContain('$1,500.00')
        ->toContain('$4,500.00')
        ->toContain('class="overdue-row"')
        ->toContain('class="overdue-extended-row"')
        ->toContain('background: #fee2e2;')
        ->toContain('background: #fef3c7;')
        ->toContain('background: #eff6ff;')
        ->toContain('color: #1d4ed8;');
});

test('email body shows separate overdue and actively extended quantities', function () {
    $html = view('emails.overdue-stage-orders-report', overdueStageReportFixture())->render();

    expect($html)
        ->toContain('Overdue Orders')
        ->toContain('Overdue Extended')
        ->toContain('Overdue Amount')
        ->toContain('Overdue Extended Amount')
        ->toContain('Total Amount')
        ->toContain('$3,000.00')
        ->toContain('$1,500.00')
        ->toContain('$4,500.00')
        ->toContain('2 overdue')
        ->toContain('1 overdue extended')
        ->toContain('background:#fee2e2')
        ->toContain('background:#fef3c7');
});
