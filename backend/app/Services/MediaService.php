<?php

namespace App\Services;

use App\Enums\MediaStatus;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Single authoritative service for storing an uploaded file and recording
 * its metadata. The disk is config-driven (local "public" disk for dev,
 * "s3" in production/staging per the architecture doc) — swapping disks
 * never requires touching this service or its callers.
 */
class MediaService
{
    public function store(UploadedFile $file, User $uploader, string $directory = 'media'): MediaAsset
    {
        $disk = Config::get('filesystems.default');
        $key = $directory.'/'.Str::uuid().'.'.$file->getClientOriginalExtension();

        $file->storeAs('', $key, $disk);

        return MediaAsset::create([
            'disk' => $disk,
            'storage_key' => $key,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'status' => MediaStatus::Ready,
            'uploaded_by' => $uploader->id,
        ]);
    }
}
