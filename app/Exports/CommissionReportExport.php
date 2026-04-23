<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CommissionReportExport implements FromView
{
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
