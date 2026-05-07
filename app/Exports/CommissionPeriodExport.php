<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesServiceExcelStyle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;

class CommissionPeriodExport implements FromView, WithEvents
{
    use AppliesServiceExcelStyle;

    public function __construct(
        private readonly array $data
    ) {
    }

    public function view(): View
    {
        return view('excels.commission-period', $this->data);
    }
}
