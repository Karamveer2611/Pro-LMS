<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(private readonly EnrollmentAccessService $access) {}

    /**
     * Admin-only manual enrollment (the only enrollment path in V1 — see
     * docs/phase0-architecture.md §6). expires_at is always computed here,
     * via EnrollmentAccessService, never passed in directly.
     */
    public function enroll(User $learner, Course $course, ?Batch $batch, User $admin, ?string $notes = null): Enrollment
    {
        // Catches the (user_id, course_id, batch_id = NULL) case the DB's
        // unique constraint can't enforce (SQL NULLs aren't unique-checked);
        // the constraint remains the backstop for the batch_id-present case.
        $duplicate = Enrollment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->where('batch_id', $batch?->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'course_id' => ['This learner already has an enrollment record for this course/batch. Extend its access instead of creating a new one.'],
            ]);
        }

        $enrolledAt = now();

        return Enrollment::create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'batch_id' => $batch?->id,
            'enrolled_at' => $enrolledAt,
            'expires_at' => $this->access->resolveExpiry($course, $batch, $enrolledAt),
            'enrolled_by' => $admin->id,
            'notes' => $notes,
        ]);
    }

    /**
     * The only way to extend access in V1 — admin sets a new expires_at
     * directly (see architecture doc §6). No self-service renewal.
     */
    public function extendAccess(Enrollment $enrollment, Carbon $newExpiresAt): Enrollment
    {
        $enrollment->update(['expires_at' => $newExpiresAt]);

        return $enrollment;
    }

    public function cancel(Enrollment $enrollment): Enrollment
    {
        $enrollment->update(['status' => EnrollmentStatus::Cancelled]);

        return $enrollment;
    }
}
