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
        ];
    }
}
