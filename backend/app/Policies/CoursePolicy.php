<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use App\Services\EnrollmentAccessService;

class CoursePolicy
{
    public function __construct(private readonly EnrollmentAccessService $access) {}

    /**
     * Anyone (including guests) may browse the catalog; visibility of
     * individual courses is narrowed in view()/the controller's query scope.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Published courses are visible to everyone (catalog browsing). A
     * non-published course is visible to an admin, or to a learner who is
     * actively enrolled in it — an admin toggling a course back to draft
     * to edit it must never lock out learners already enrolled.
     */
    public function view(?User $user, Course $course): bool
    {
        if ($course->status === CourseStatus::Published) {
            return true;
        }

        if ($user?->isAdmin()) {
            return true;
        }

        return $user !== null && $this->access->canAccessCourse($user, $course);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }

    /**
     * Single authoritative check for all module/section/lesson mutations
     * within a course — content management is an admin-only action in V1.
     */
    public function manageContent(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }
}
