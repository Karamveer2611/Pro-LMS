<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'status' => $this->status,
            'notes' => $this->notes,
            'marked_by' => $this->marked_by,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
