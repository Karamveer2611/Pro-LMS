<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Resources\MediaAssetResource;
use App\Services\MediaService;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function store(StoreMediaRequest $request): MediaAssetResource
    {
        $asset = $this->media->store($request->file('file'), $request->user());

        return new MediaAssetResource($asset);
    }
}
