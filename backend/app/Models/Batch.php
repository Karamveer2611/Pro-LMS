<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'name', 'start_date', 'end_date', 'status', 'access_days_override', 'capacity'])]
class Batch extends Model
{
    use HasFactory;

    /**
     * Mirrors the `status` column's DB default — see User::$attributes for
     * why this matters for create() responses.
     */
    protected $attributes = [
        'status' => 'upcoming',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => BatchStatus::class,
            'access_days_override' => 'integer',
            'capacity' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'batch_instructor')->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function liveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }
}
