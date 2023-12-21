<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\Product;
use Inertia\Inertia;

class PdfController extends Controller
{
    use Product;

    public function workOrder(Request $request)
    {
      return Inertia::render('Pdf/WorkOrder', []);
    }
}
