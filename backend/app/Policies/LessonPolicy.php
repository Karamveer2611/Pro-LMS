<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * Whether the full lesson content (video/document/text body) may be viewed —
     * not just its title/position in the curriculum.
     *
     * V1 (no enrollment system yet): admin, or a preview lesson, only.
     * TODO(Week 4): also allow when the user holds active enrollment access to
     * the lesson's course (EnrollmentAccessService) — this is the only place
     * that check may be added; do not duplicate it elsewhere.
     */
    public function viewContent(?User $user, Lesson $lesson): bool
    {
        if ($lesson->is_preview) {
            return true;
        }

        return $user?->isAdmin() ?? false;
    }
}
