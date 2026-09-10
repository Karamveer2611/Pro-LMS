<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'disk' => 'public',
            'storage_key' => 'media/'.Str::uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1000, 500000),
            'status' => MediaStatus::Ready,
            'uploaded_by' => User::factory()->admin(),
        ];
    }
}
