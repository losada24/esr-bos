<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enum\StatusUserEnum;

class UserResource extends JsonResource
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
          'email' => $this->email ?? '',
          'phone' => $this->phone ?? '',
          'role' => $this->roles->all() ?? [],
          'delegated_owner_ids' => $this->delegatedOwners()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all(),
          'reference_code' => $this->reference_code ?? '',
          'created_at' => $this->created_at,
          'updated_at' => $this->updated_at,
          'company_id' => $this->company_id ?? '',
          'markup' => $this->markup ?? 0,
          'featured_image' => $this->featured_image ? asset('storage/'.$this->featured_image) : '',
          'status' => $this->status ?? StatusUserEnum::ACTIVE->value,
        ];
    }
}
