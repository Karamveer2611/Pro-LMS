<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;
use App\Services\EnrollmentAccessService;

class LessonPolicy
{
    public function __construct(private readonly EnrollmentAccessService $access) {}

    /**
     * Whether the full lesson content (video/document/text body) may be
     * viewed — not just its title/position in the curriculum. Single
     * authoritative check, backed by EnrollmentAccessService::lessonAccessState
     * (preview lessons, admin, and enrolled-and-unlocked learners all pass).
     */
    public function viewContent(?User $user, Lesson $lesson): bool
    {
        return $this->access->lessonAccessState($user, $lesson)['is_unlocked'];
    }
}
