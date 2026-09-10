<?php

namespace App\Models;

use App\Enums\LessonReleaseType;
use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'section_id', 'title', 'type', 'content_body', 'media_id', 'order',
    'is_published', 'is_preview', 'is_required', 'release_type', 'release_days', 'release_date',
])]
class Lesson extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'release_type' => LessonReleaseType::class,
            'order' => 'integer',
            'is_published' => 'boolean',
            'is_preview' => 'boolean',
            'is_required' => 'boolean',
            'release_days' => 'integer',
            'release_date' => 'date',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }
}
