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
          'frame_color' => $this->frame_color ?? '',
          'glass_color' => $this->glass_color ?? '',
          'markup' => $this->markup ?? '',
          'notes' => $this->notes ?? '',
        ];
    }
}
