<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Models\Order;
use App\Http\Requests\StoreFixedWindowsRequest;
use App\Http\Requests\UpdateFixedWindowsRequest;
use App\Actions\CreateFixedWindows;
use App\Models\Product;
use App\Actions\UpdateFixedWindows;
use App\Enum\MuntinPatternEnum;
use App\Enum\MuntinStyleEnum;

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
        'muntin_patterns' => array_values(MuntinPatternEnum::$MUNTIN_PATTERN),
        'muntin_styles' => array_values(MuntinStyleEnum::$MUNTIN_STYLE),
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
        return redirect()->route('estimate.show', ['estimate' => $storeFixWindowsRequest->order_id])
          ->with('success', 'Fixed Windows created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
      $product->loadMissing('order');
      return Inertia::render('FixedWindows/Edit', [
          'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
          'muntin_patterns' => array_values(MuntinPatternEnum::$MUNTIN_PATTERN),
          'muntin_styles' => array_values(MuntinStyleEnum::$MUNTIN_STYLE),
          'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFixedWindowsRequest $updateFixedWindowsRequest, UpdateFixedWindows $updateFixedWindows, Product $product)
    {
        $updateFixedWindows->handle($updateFixedWindowsRequest, $product);
        return redirect()->route('estimate.show', ['estimate' => $product->order_id])
          ->with('success', 'Fixed Windows updated successfully.');
    }
}
