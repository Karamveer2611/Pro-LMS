<?php

namespace App\Models;

use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'disk', 'storage_key', 'original_filename', 'mime_type', 'size_bytes',
    'video_provider', 'video_provider_asset_id', 'duration_seconds', 'status', 'uploaded_by',
])]
class MediaAsset extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MediaStatus::class,
            'size_bytes' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }
}
