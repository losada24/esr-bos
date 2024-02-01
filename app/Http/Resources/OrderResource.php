<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
          'id' => $this->id,
          'name' => $this->name ?? '',
          'status' => $this->status ?? '',
          'project_name' => $this->project_name ?? '',
          'external_purchase_id' => $this->external_purchase_id ?? '',
          'frame_color' => $this->frame_color ?? '',
          'glass_color' => $this->glass_color ?? '',
          'glass_type' => $this->glass_type ?? '',
          'markup' => $this->markup ?? '',
          'notes' => $this->notes ?? '',
          'created_at' => $this->created_at,
          'productsCount' => $this->productsCount ?? 0,
          'installation' => $this->installation ?? 0,
          'tax_amount' => $this->tax_amount ?? 0,
          'tax_rate' => $this->tax_rate ?? 0,
          'permit' => $this->permit ?? 0,
          'other' => $this->other ?? 0,
          'products' => $this->products ?? [],
          'company_markup' => $this->company_markup ?? 0,
          'company_promotion' => $this->company_promotion ?? 0,
          'user_markup' => $this->user_markup ?? 0,
          'user_id' => $this->user_id,
          'rg_other_price' => $this->rg_other_price ?? 0,
          'order_promotion' => $this->order_promotion ?? 0,
          'subdealer_other' => $this->subdealer_other ?? 0,
        ];
    }
}
