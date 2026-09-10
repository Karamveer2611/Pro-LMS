<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Anyone (including guests) may browse the catalog; visibility of
     * individual courses is narrowed in view()/the controller's query scope.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        return $course->status === CourseStatus::Published || ($user?->isAdmin() ?? false);
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
