<?php

namespace App\Http\Controllers;

use App\Enum\ProductSystemEnum;
use App\Models\Order;
use App\Traits\Product;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    use Product;

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
      
      $order->load(['products', 'client']);
      $orderCuttingList = $this->orderedCuttingList($order);

      $callback = function() use ($orderCuttingList, $columns, $order) {
          $file = fopen('php://output', 'w');
          fputcsv($file, $columns);

          foreach ($orderCuttingList as $product) {
            foreach ($product['items'] as $item) {
              for ($i = 0; $i < $item['qty']; $i++) {
                fputcsv($file, [
                  $order->quoteNumber,
                  $product['material'],
                  $item['line_item_name'],
                  $item['size'],
                  ProductSystemEnum::getSystemNameAbbr($item['system']) . "/" . $item['part'],
                  $i + 1 . "/" . $item['qty'],
                  config('custom.labels_images_path') . $item['visual_id'] + 1 . ".jpg"
                ]);
              }
            }
          }
          
          fclose($file);
      };

      return response()->stream($callback, 200, $headers);    
    }

    public function productLabels(Order $order) {
      $fileName = 'Order.csv';
      $headers = array(
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"  => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
      );

      $columns = array('Order', 'Material', 'Mark', 'Size', 'Part', 'Qty', 'Image');
      
      $order->load(['products', 'client']);
      $orderCuttingList = $this->orderedCuttingList($order);

      $callback = function() use ($orderCuttingList, $columns, $order) {
          $file = fopen('php://output', 'w');
          fputcsv($file, $columns);

          foreach ($orderCuttingList as $product) {
            foreach ($product['items'] as $item) {
              for ($i = 0; $i < $item['qty']; $i++) {
                fputcsv($file, [
                  $order->quoteNumber,
                  $product['material'],
                  $item['line_item_name'],
                  $item['size'],
                  ProductSystemEnum::getSystemNameAbbr($item['system']) . "/" . $item['part'],
                  $i + 1 . "/" . $item['qty'],
                  config('custom.labels_images_path') . $item['visual_id'] + 1 . ".jpg"
                ]);
              }
            }
          }
          
          fclose($file);
      };

      return response()->stream($callback, 200, $headers);    
    }
}
