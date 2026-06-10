<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesServiceExcelStyle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;

class OwnerAssignedSummaryExport implements FromView, WithEvents
{
    use AppliesServiceExcelStyle;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('excels.owner-assigned-summary', $this->data);
    }
}
