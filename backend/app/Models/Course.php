<?php

namespace App\Models;

use App\Enums\CourseFormat;
use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title', 'slug', 'short_description', 'description', 'thumbnail_media_id',
    'format', 'price', 'discount_price', 'currency', 'default_access_days',
    'status', 'created_by',
])]
class Course extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mirrors the `currency` column's DB default — see User::$attributes
     * for why this matters for create() responses.
     */
    protected $attributes = [
        'currency' => 'INR',
    ];

    protected function casts(): array
    {
        return [
            'format' => CourseFormat::class,
            'status' => CourseStatus::class,
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'default_access_days' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'thumbnail_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'course_category');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
