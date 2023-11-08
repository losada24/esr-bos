<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Models\Order;

class FixWindowsController extends Controller
{
  public function create($id)
  {
      return Inertia::render('FixWindows/Create', [
        'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
        'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
        'estimate' => Order::with(['client'])->findOrFail($id),
      ]);
  }
}
