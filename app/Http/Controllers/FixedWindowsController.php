<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Models\Order;
use App\Http\Requests\StoreFixedWindowsRequest;
use App\Actions\CreateFixedWindows;

class FixedWindowsController extends Controller
{
  /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

  public function create($id)
  {
      return Inertia::render('FixedWindows/Create', [
        'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
        'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
        'estimate' => Order::with(['client'])->withCount(['products'])->findOrFail($id),
      ]);
  }

  /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFixedWindowsRequest $storeFixWindowsRequest, CreateFixedWindows $createFixWindows)
    {
        $createFixWindows->handle($storeFixWindowsRequest);
        return redirect()->route('estimate.show', ['estimate' => $storeFixWindowsRequest->order_id]);
    }
}
