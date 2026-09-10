<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'batch_name' => $this->whenLoaded('batch', fn () => $this->batch->name),
            'instructor' => new UserResource($this->whenLoaded('instructor')),
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'meeting_provider' => $this->meeting_provider,
            'meeting_link' => $this->meeting_link,
            'status' => $this->status,
            'recording_url' => $this->recording_url,
        ];
    }
}
