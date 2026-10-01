<?php

use App\Exports\OverdueStageOrdersExport;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            'orders' => 4,
            'not_overdue_orders' => 1,
            'not_overdue_amount' => 500,
            'overdue_orders' => 2,
            'overdue_extended_orders' => 1,
            'overdue_amount' => 3000,
            'overdue_extended_amount' => 1500,
            'amount' => 5000,
        ],
        'groups' => [[
            'status' => 'PRODUCTION',
            'threshold_label' => '25 business days',
            'note' => 'Overdue after more than 25 business days in PRODUCTION.',
            'is_configured' => true,
            'not_overdue_count' => 1,
            'not_overdue_amount' => 500,
            'overdue_count' => 2,
            'overdue_extended_count' => 1,
            'overdue_amount' => 3000,
            'overdue_extended_amount' => 1500,
            'amount' => 5000,
            'count' => 4,
            'seller_groups' => [[
                'label' => 'Test Seller',
                'source' => 'seller',
                'count' => 4,
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
                    [
                        'id' => 104,
                        'status' => 'PRODUCTION',
                        'order_label' => '#104 - Not overdue order',
                        'amount' => 500,
                        'days_in_stage' => 10,
                        'order_type' => 'Residential',
                        'product_line' => 'ESR',
                        'stage_entered_at' => '2026-08-17 08:00:00',
                        'is_overdue' => false,
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
        ->toContain('Not Overdue Orders')
        ->toContain('Not Overdue Amount')
        ->toContain('Overdue Extended')
        ->toContain('Overdue Amount')
        ->toContain('Overdue Extended Amount')
        ->toContain('Total Amount')
        ->toContain('$3,000.00')
        ->toContain('$1,500.00')
        ->toContain('$5,000.00')
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
        ->toContain('Not Overdue Orders')
        ->toContain('Not Overdue Amount')
        ->toContain('Overdue Extended')
        ->toContain('Overdue Amount')
        ->toContain('Overdue Extended Amount')
        ->toContain('Total Amount')
        ->toContain('$3,000.00')
        ->toContain('$1,500.00')
        ->toContain('$5,000.00')
        ->toContain('2 overdue')
        ->toContain('1 overdue extended')
        ->toContain('background:#fee2e2')
        ->toContain('background:#fef3c7');
});

test('excel reports and colors each order according to its actual deadline status', function () {
    $temporaryFile = tmpfile();
    $temporaryPath = stream_get_meta_data($temporaryFile)['uri'];
    $contents = Excel::raw(
        new OverdueStageOrdersExport(overdueStageReportFixture()),
        ExcelWriter::XLSX
    );
    fwrite($temporaryFile, $contents);

    $sheet = IOFactory::load($temporaryPath)->getActiveSheet();

    expect($sheet->getCell('B3')->getValue())->toBe(1)
        ->and($sheet->getCell('D3')->getValue())->toBe(4)
        ->and($sheet->getCell('F3')->getValue())->toBe(1)
        ->and($sheet->getCell('H3')->getValue())->toBe(2)
        ->and($sheet->getCell('J3')->getValue())->toBe(1)
        ->and($sheet->getCell('B4')->getValue())->toBe(500)
        ->and($sheet->getCell('D4')->getValue())->toBe(3000)
        ->and($sheet->getCell('F4')->getValue())->toBe(1500)
        ->and($sheet->getCell('H4')->getValue())->toBe(5000)
        ->and($sheet->getCell('J6')->getValue())->toBe('Deadline Status')
        ->and($sheet->getCell('J9')->getValue())->toBe('Overdue')
        ->and($sheet->getCell('J10')->getValue())->toBe('Overdue Extended')
        ->and($sheet->getCell('J11')->getValue())->toBe('Overdue')
        ->and($sheet->getCell('J12')->getValue())->toBe('Not Overdue')
        ->and($sheet->getStyle('A9')->getFill()->getStartColor()->getARGB())->toBe('FFFEE2E2')
        ->and($sheet->getStyle('A10')->getFill()->getStartColor()->getARGB())->toBe('FFFEF3C7')
        ->and($sheet->getStyle('A11')->getFill()->getStartColor()->getARGB())->toBe('FFFEE2E2')
        ->and($sheet->getStyle('A12')->getFill()->getStartColor()->getARGB())->toBe('FFECFDF5');

    fclose($temporaryFile);
});
