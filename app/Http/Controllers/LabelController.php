<?php

namespace App\Http\Controllers;

use App\Enum\ProductSystemEnum;
use App\Models\Order;
use App\Traits\Fractions;
use App\Traits\Product;
use Illuminate\Http\Request;
use Illuminate\Contracts\Database\Eloquent\Builder;

class LabelController extends Controller
{
    use Product, Fractions;

    public function labelsByPieces(Order $order) {
      $fileName = 'Orders.csv';
      $headers = array(
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"  => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
      );

      $columns = array('Order', 'Material', 'Mark', 'Size', 'Part', 'Qty', 'Image');
      
      $order->load(['products' => function (Builder $query) {
        $query->whereIn('system', [
          ProductSystemEnum::$FIXED_WINDOWS,
          ProductSystemEnum::$SINGLE_HUNG,
          ProductSystemEnum::$HORIZONTAL_ROLLER
        ]);
    } , 'client']);
      $orderCuttingList = $this->orderedCuttingList($order);
      $callback = function() use ($orderCuttingList, $columns, $order) {
          $file = fopen('php://output', 'w');
          fputcsv($file, $columns);
          $labels = [];
          foreach ($orderCuttingList as $product) {
            foreach ($product['items'] as $item) {
              for ($i = 0; $i < $item['qty']; $i++) {
                $labelsObject = new \stdClass();
                $labelsObject->quoteNumber = $order->quoteNumber;
                $labelsObject->material = $product['material'];
                $labelsObject->line_item_name = $item['line_item_name'];
                $labelsObject->size = $item['size'];
                $labelsObject->system = ProductSystemEnum::getSystemNameAbbr($item['system']) . "(" .$item['extras']['config'] . ")/" . $item['part'];
                $labelsObject->qty = $i + 1 . "/" . $item['qty'];
                $labelsObject->image = config('custom.labels_images_path') . $item['visual_id'] + 1 . ".jpg";
                $labels[] = $labelsObject;
              }
            }
          }

          $reverseCuttingList = array_reverse($labels);
          foreach ($reverseCuttingList as $label) {
            fputcsv($file, [
              $label->quoteNumber,
              $label->material,
              $label->line_item_name,
              $label->size,
              $label->system,
              $label->qty,
              $label->image
            ]);
          }
          
          fclose($file);
      };

      return response()->stream($callback, 200, $headers);    
    }

    public function productLabels(Order $order) {
      $fileName = 'Products.csv';
      $headers = array(
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"  => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
      );

      $columns = array('Quote', 'Mark', 'Client', 'Project', 'System', 'Frame', 'Config', 'Glass Type', 'Size', 'Pressure', 'NOA Number');
      
      $order->load(['products', 'company']);
      
      $callback = function() use ($order, $columns) {
          $file = fopen('php://output', 'w');
          fputcsv($file, $columns);

          foreach ($order->products as $product) {
            for ($i = 0; $i < $product->qty; $i++) {
              fputcsv($file, [
                $order->quoteNumber,
                $product->line_item_name,
                $order->company->name,
                $order->project_name,
                $product->system,
                $product->frame_color,
                isset($product->extras['config']) ? $product->extras['config'] : '',
                $product->glass_type,
                $this->getNumberWithFraction($product->width)  . " x " . $this->getNumberWithFraction($product->height) . " inches",
                ProductSystemEnum::getSystemPressure($product->system),
                ProductSystemEnum::getSystemNoa($product->system)
              ]);
            }
          }

          fclose($file);
      };

      return response()->stream($callback, 200, $headers);    
    }
}
