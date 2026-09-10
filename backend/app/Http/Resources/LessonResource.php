<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Curriculum-view resource: structure and metadata only. Never includes
 * content_body/media — that requires passing LessonPolicy::viewContent,
 * handled separately by LessonContentResource via a dedicated endpoint.
 */
class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'order' => $this->order,
            'is_published' => $this->is_published,
            'is_preview' => $this->is_preview,
            'is_required' => $this->is_required,
            'release_type' => $this->release_type,
            'release_days' => $this->release_days,
            'release_date' => $this->release_date?->toDateString(),
        ];
    }
}
