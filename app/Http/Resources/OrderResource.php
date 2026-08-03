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
          'city' => $this->city ?? '',
          'job_state' => $this->job_state ?? '',
          'job_zip' => $this->job_zip ?? '',
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
          'installation_end_date' => $this->installation_end_date ?? '',
          'service' => $this->service ?? '',
          'is_supply' => (bool) ($this->is_supply ?? false),
          'install_by_phases' => (bool) ($this->install_by_phases ?? false),
          'phases_count' => $this->whenLoaded('phases', fn () => $this->phases->count(), 0),
          'phases_completed_count' => $this->whenLoaded('phases', fn () => $this->phases->where('status', 'COMPLETE')->count(), 0),
          'next_phase' => $this->whenLoaded('phases', function () {
            $phase = $this->phases
              ->where('status', '!=', 'COMPLETE')
              ->sortBy('installation_date')
              ->first();

            return $phase ? [
              'id' => $phase->id,
              'name' => $phase->name,
              'status' => $phase->status,
              'installation_date' => $phase->installation_date ? $phase->installation_date->format('Y-m-d') : null,
            ] : null;
          }),
        ];
    }
}
