<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
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
          'filename' => $this->filename ?? '',
          'file_path' => $this->file_path ? asset('storage/'.$this->file_path) : '',
          'file_type' => $this->file_type ?? '',
        ];
    }
}
