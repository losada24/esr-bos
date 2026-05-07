<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesServiceExcelStyle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;

class CommissionReportExport implements FromView, WithEvents
{
    use AppliesServiceExcelStyle;

    public function __construct(
        private readonly array $data
    ) {
    }

    public function view(): View
    {
        $view = ($this->data['selectedView'] ?? 'commissions') === 'payments'
            ? 'excels.commission-payments'
            : 'excels.commissions';

        return view($view, $this->data);
    }
}
