<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
          'phone_number' => $this->phone_number ?? '',
          'email' => $this->email ?? '',
          'address' => $this->address ?? '',
          'city' => $this->city ?? '',
          'state' => $this->state ?? '',
          'zip' => $this->zip ?? '',
          'featured_image' => $this->featured_image ? asset('storage/'.$this->featured_image) : '',
          'promotion' => $this->promotion ?? 0,
          'markup' => $this->markup ?? 0,
          'external_products_markup' => $this->external_products_markup ?? 0,
          'allow_credit_payment' => $this->allow_credit_payment ?? '',
        ];
    }
}
