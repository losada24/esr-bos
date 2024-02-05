<?php

namespace App\Traits;

use App\Enum\RoleEnum;

trait Prices {

    public function getUnitPriceByRole($product, $role) {
      if (in_array(RoleEnum::$SUB_DEALER, $role)) {
        return $product->sub_dealer_unit_price;
      } else if (in_array(RoleEnum::$DEALER, $role)) {
        return $product->dealer_unit_price;
      }
    }

    public function getTotalPriceByRole($product, $role) {
      if (in_array(RoleEnum::$SUB_DEALER, $role)) {
        return $product->sub_dealer_total_price;
      } else if (in_array(RoleEnum::$DEALER, $role)) {
        return $product->dealer_total_price;
      }
    }

    public function getSubtotalByRole($products, $role) {
      $subtotal = 0;
      foreach ($products as $product) {
        $subtotal += $this->getTotalPriceByRole($product, $role);
      }
      return $subtotal;
    }

    public function getDealerPromotion($products, $role) {
      if (in_array(RoleEnum::$DEALER, $role)) {
        $subtotal = 0;
        foreach ($products as $product) {
           $subtotal += (double)$product->dealer_promotion_total_discount;
        }
        return $subtotal;
      }

      return 0;
    }

    public function getGrandTotalByRole($order, $role) {
      $subtotal = $this->getSubtotalByRole($order->products, $role);
      $dealerPromotion = $this->getDealerPromotion($order->products, $role);
      $orderPromotion = $order->order_promotion;
      $rgOtherPrice = $order->rg_other_price;

      return $subtotal - $dealerPromotion - $orderPromotion + $rgOtherPrice;
    }

    public function formatPrice($price) {
      $formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
      return $formatter->formatCurrency($price, 'USD');
    }

}