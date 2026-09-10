<?php

namespace App\Policies;

use App\Models\User;

class AttendancePolicy
{
    /**
     * Ownership of *marking* a session's attendance lives on
     * LiveSessionPolicy::manageAttendance() — Laravel resolves policies by
     * the model being authorized against, and that check is against a
     * LiveSession, not an Attendance row. This class only covers the
     * class-level "can view their own history" ability.
     */
    public function viewOwn(User $user): bool
    {
        return $user->isLearner();
    }
}
