<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RawMaterialResource extends JsonResource
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
          'qty' => $this->qty ?? '',
          'unit_of_measurement' => $this->unit_of_measurement ?? '',
          'cost_per_unit' => $this->cost_per_unit ?? '',
          'featured_image' => $this->featured_image ? asset('storage/'.$this->featured_image) : '',
          'notes' => $this->notes ?? '',
        ];
    }
}
