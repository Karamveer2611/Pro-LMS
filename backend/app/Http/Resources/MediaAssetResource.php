<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'video_provider' => $this->video_provider,
            'duration_seconds' => $this->duration_seconds,
            'status' => $this->status,
            'url' => Storage::disk($this->disk)->url($this->storage_key),
        ];
    }
}
