<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Models\Order;
use App\Http\Requests\StoreSingleHuntRequest;
use App\Actions\CreateSingleHunt;
use App\Models\Product;
use App\Actions\UpdateSingleHunt;
use App\Enum\GlassTypeEnum;
use App\Enum\MuntinPatternEnum;
use App\Enum\MuntinStyleEnum;
use App\Http\Requests\UpdateSingleHuntRequest;

class SingleHuntController extends Controller
{
  /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

  public function create($id)
  {
      $order = Order::with(['client'])->withCount(['products'])->findOrFail($id);
      $glass_colors = GlassColorEnum::$GLASS_COLOR;
      if ($order->glass_type == GlassTypeEnum::$REGULAR_GLASS_TYPE) {
        $glass_colors = GlassColorEnum::getRegularGlassColor();
      }

      return Inertia::render('SingleHunt/Create', [
        'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
        'glass_colors' => array_values($glass_colors),
        'muntin_patterns' => array_values(MuntinPatternEnum::$MUNTIN_PATTERN),
        'muntin_styles' => array_values(MuntinStyleEnum::$MUNTIN_STYLE),
        'estimate' => $order,
      ]);
  }

  /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSingleHuntRequest $storeSingleHuntRequest, CreateSingleHunt $createSingleHunt)
    {
        $createSingleHunt->handle($storeSingleHuntRequest);
        return redirect()->route('estimate.show', ['estimate' => $storeSingleHuntRequest->order_id])
          ->with('success', 'Single Hunt created successfully.');
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
      $glass_colors = GlassColorEnum::$GLASS_COLOR;
      if ($product->order->glass_type == GlassTypeEnum::$REGULAR_GLASS_TYPE) {
        $glass_colors = GlassColorEnum::getRegularGlassColor();
      }
      
      return Inertia::render('SingleHunt/Edit', [
          'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          'glass_colors' => array_values($glass_colors),
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
    public function update(UpdateSingleHuntRequest $updateFixedWindowsRequest, UpdateSingleHunt $updateFixedWindows, Product $product)
    {
        $updateFixedWindows->handle($updateFixedWindowsRequest, $product);
        return redirect()->route('estimate.show', ['estimate' => $product->order_id])
          ->with('success', 'Single Hunt updated successfully.');
    }
}
