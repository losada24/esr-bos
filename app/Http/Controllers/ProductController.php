<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\Product;

class ProductController extends Controller
{
     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $estimate = $product->order_id;
        $product->delete();
        return redirect()
          ->route('estimate.show', ['estimate' => $estimate])
          ->with('success', 'Product deleted successfully.');
    }

    public function duplicate(Product $product)
    {
        $estimate = $product->order_id;
        $newProduct = $product->replicate();
        $newProduct->save();

        return redirect()
          ->route('estimate.show', ['estimate' => $estimate])
          ->with('success', 'Product duplicated successfully.');
    }
}
