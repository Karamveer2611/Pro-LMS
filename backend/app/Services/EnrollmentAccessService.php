<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\LessonReleaseType;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * THE single authoritative implementation of enrollment access, expiry, and
 * drip-content resolution (docs/phase0-architecture.md §6-7). No other class
 * is allowed to duplicate this logic — controllers, policies, and resources
 * all call into this service rather than re-deriving "is this accessible".
 */
class EnrollmentAccessService
{
    /**
     * Resolve what expires_at should be for a new enrollment, given the
     * course's default and an optional batch override.
     */
    public function resolveExpiry(Course $course, ?Batch $batch, Carbon $enrolledAt): ?Carbon
    {
        $days = $batch?->access_days_override ?? $course->default_access_days;

        return $days ? Carbon::parse($enrolledAt)->addDays($days) : null;
    }

    /**
     * The learner's currently-active, non-expired enrollment for a course,
     * if any. This is the one query every other access check builds on.
     */
    public function activeEnrollment(User $user, Course $course): ?Enrollment
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('batch')
            ->first();
    }

    public function canAccessCourse(?User $user, Course $course): bool
    {
        return $user !== null && $this->activeEnrollment($user, $course) !== null;
    }

    /**
     * When a lesson becomes visible for a given enrollment, per its
     * configured drip rule. Null means "never resolvable" (e.g. a
     * days-after-batch-start lesson on a non-batch enrollment).
     */
    public function lessonUnlocksAt(Lesson $lesson, Enrollment $enrollment): ?Carbon
    {
        return match ($lesson->release_type) {
            LessonReleaseType::Immediate => Carbon::parse($enrollment->enrolled_at),
            LessonReleaseType::DaysAfterEnrollment => Carbon::parse($enrollment->enrolled_at)->addDays($lesson->release_days),
            LessonReleaseType::FixedDate => $lesson->release_date ? Carbon::parse($lesson->release_date) : null,
            LessonReleaseType::DaysAfterBatchStart => $enrollment->batch
                ? Carbon::parse($enrollment->batch->start_date)->addDays($lesson->release_days)
                : null,
        };
    }

    /**
     * Full access-state resolution for one lesson, for one viewer — the
     * single function every content-gating check and every curriculum
     * listing must call, so "can they see this" is never computed twice.
     *
     * @return array{is_unlocked: bool, unlocked_at: ?Carbon}
     */
    public function lessonAccessState(?User $user, Lesson $lesson): array
    {
        if ($lesson->is_preview) {
            return ['is_unlocked' => true, 'unlocked_at' => null];
        }

        if ($user?->isAdmin()) {
            return ['is_unlocked' => true, 'unlocked_at' => null];
        }

        if (! $user) {
            return ['is_unlocked' => false, 'unlocked_at' => null];
        }

        $course = $lesson->section->module->course;
        $enrollment = $this->activeEnrollment($user, $course);

        if (! $enrollment) {
            return ['is_unlocked' => false, 'unlocked_at' => null];
        }

        $unlocksAt = $this->lessonUnlocksAt($lesson, $enrollment);

        return [
            'is_unlocked' => $unlocksAt !== null && $unlocksAt->isPast(),
            'unlocked_at' => $unlocksAt,
        ];
    }

    /**
     * Flip every active-but-past-expiry enrollment to expired. Called by
     * the daily scheduled sweep; access checks are also enforced lazily on
     * every request via activeEnrollment()'s own expiry check, so a learner
     * is never granted access between expiry and the next sweep.
     */
    public function sweepExpired(): int
    {
        return Enrollment::query()
            ->where('status', EnrollmentStatus::Active)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => EnrollmentStatus::Expired]);
    }
}
