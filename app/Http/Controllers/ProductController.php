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

     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function bulkDestroy(Request $request)
    {
        Product::destroy($request->products);
        return redirect()
          ->route('estimate.show', ['estimate' => $request->order_id])
          ->with('success', 'Products deleted successfully.');
    }

    public function duplicate(Product $product)
    {
        $estimate = $product->order_id;
        $newProduct = $product->replicate();
        $newProduct->line_item_name = $newProduct->line_item_name . ' (copy)';
        $newProduct->save();

        return redirect()
          ->route('estimate.show', ['estimate' => $estimate])
          ->with('success', 'Product duplicated successfully.');
    }

    public function sort(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->products()->each(function ($product) use ($request) {
            $order = array_search($product->id, array_column($request->products, 'id'));
            $product->update([
              'product_sort' => $request->products[$order]['order']
            ]);
        });

        return redirect()
          ->route('estimate.show', ['estimate' => $request->order_id]);
    }
}
