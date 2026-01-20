<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SupervisorAssignedSummaryExport implements FromView
{
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('excels.supervisor-assigned-summary', $this->data);
    }
}
