<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;

class ProductController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        return Inertia::render('Product/Index', [
          // 'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          // 'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
          'estimate' => Order::with(['client', 'products'])->findOrFail($id),
        ]);
    }
}
