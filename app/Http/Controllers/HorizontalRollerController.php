<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Models\Order;
use App\Http\Requests\StoreHorizontalRollerRequest;
use App\Http\Requests\UpdateFixedWindowsRequest;
use App\Actions\CreateHorizontalRoller;
use App\Models\Product;
use App\Actions\UpdateHorizontalRoller;
use App\Enum\HorizontalRollerConfigEnum;
use App\Enum\HorizontalRollerHandleEnum;
use App\Enum\MuntinPatternEnum;
use App\Enum\MuntinStyleEnum;

class HorizontalRollerController extends Controller
{
  /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

  public function create($id)
  {
      return Inertia::render('HorizontalRoller/Create', [
        'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
        'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
        'config' => array_values(HorizontalRollerConfigEnum::$CONFIG),
        'handle' => array_values(HorizontalRollerHandleEnum::$HANDLE),
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
    public function store(StoreHorizontalRollerRequest $storeHorizontalRollerRequest, CreateHorizontalRoller $createHorizontalRoller)
    {
        $createHorizontalRoller->handle($storeHorizontalRollerRequest);
        return redirect()->route('estimate.show', ['estimate' => $storeHorizontalRollerRequest->order_id])
          ->with('success', 'Horizontal Roller created successfully.');
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
      return Inertia::render('HorizontalRoller/Edit', [
          'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
          'config' => array_values(HorizontalRollerConfigEnum::$CONFIG),
          'handle' => array_values(HorizontalRollerHandleEnum::$HANDLE),
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
    public function update(UpdateFixedWindowsRequest $updateFixedWindowsRequest, UpdateHorizontalRoller $updateFixedWindows, Product $product)
    {
        $updateFixedWindows->handle($updateFixedWindowsRequest, $product);
        return redirect()->route('estimate.show', ['estimate' => $product->order_id])
          ->with('success', 'Horizontal Roller updated successfully.');
    }
}
