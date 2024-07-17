<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallationTeamResource extends JsonResource
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
          'user_id' => $this->user_id ?? '',
          'number_of_member' => $this->number_of_member ?? '',
          'worker_compensation_expiration_date' => $this->worker_compensation_expiration_date ?? '',
          'liability_expiration_date' => $this->liability_expiration_date ?? '',
          'notes' => $this->notes ?? '',
          'user' => $this->user ?? '',
          'type_housing' => $this->typeHousing ?? '',
          'attachments' => $this->attachments ? $this->attachments->transform(function($attachment) {
            return new AttachmentResource($attachment);
          }) : []
        ];
    }
}
