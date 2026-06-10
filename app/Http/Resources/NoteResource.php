<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Note */
class NoteResource extends JsonResource
{
    /**
     * Serializa una Nota al shape que espera el frontend.
     * Formato:
     *  {
     *    id, content, type, created_at,
     *    user: { name }
     *  }
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'content'    => $this->content,
            'type'       => $this->type,
            'created_at' => optional($this->created_at)->toISOString(),
            'user'       => $this->whenLoaded('user', function () {
                return [
                    'name' => (string) optional($this->user)->name,
                ];
            }),
        ];
    }
}