<?php

namespace App\Policies;

use App\Models\LiveSession;
use App\Models\User;

class LiveSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, LiveSession $session): bool
    {
        return $user->isAdmin() || $session->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Single authoritative ownership check for both viewing a session's
     * attendance roster and marking it — a trainer may only touch
     * attendance for a session they are the assigned instructor on; admin
     * always can. Lives here (not AttendancePolicy) because Laravel
     * resolves policies by the authorized model's class, and the model in
     * play here is the LiveSession, not an Attendance row.
     */
    public function manageAttendance(User $user, LiveSession $session): bool
    {
        return $user->isAdmin() || $session->instructor_id === $user->id;
    }
}
