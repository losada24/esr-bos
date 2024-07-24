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
          'order_number' => $this->order_number ?? '',
          'job_address' => $this->job_address ?? '',
          'name' => $this->name ?? '',
          'status' => $this->status ?? '',
          'client' => $this->client ?? '',
          'installation_teams' => $this->installationTeams ?? '',
          'supervisor' => $this->supervisor ?? '',
          'entry_date' => $this->entry_date ?? '',
          'contract_signing_date' => $this->contract_signing_date ?? '',
          'payment_factory_date' => $this->payment_factory_date ?? '',
          'delivery_date' => $this->delivery_date ?? '',
          'installation_date' => $this->installation_date ?? '',
          'service' => $this->service ?? '',
        ];
    }
}
