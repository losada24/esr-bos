<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SalesAppointmentsBySellerExport implements FromView
{
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('excels.sales-appointments-by-seller', $this->data);
    }
}
