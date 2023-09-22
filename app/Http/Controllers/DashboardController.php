<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Traits\Reports;

class DashboardController extends Controller
{
  use Reports;

  public function index(Request $request): Response
  {
      return Inertia::render('Dashboard/Index', [
        'referralsByMonth' => $this->GetReferralsByMonths(),
        'referralsByStatus' => $this->GetReferralsByStatus()
      ]);
  }
}
