<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CommissionPeriodExport implements FromView
{
    public function __construct(
        private readonly array $data
    ) {
    }

    public function view(): View
    {
        return view('excels.commission-period', $this->data);
    }
}
