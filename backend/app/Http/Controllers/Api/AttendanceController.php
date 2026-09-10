<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BulkAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\UserResource;
use App\Models\Attendance;
use App\Models\LiveSession;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function index(LiveSession $session)
    {
        $this->authorize('manageAttendance', $session);

        return AttendanceResource::collection($session->attendance()->with('user')->get());
    }

    /**
     * The batch roster this session's attendance may be marked against —
     * every learner with an active enrollment in the session's batch,
     * matching the same set BulkAttendanceRequest validates records.*.user_id
     * against. Lets the trainer's UI show everyone, not just already-marked
     * learners, without a separate enrollments-listing permission.
     */
    public function roster(LiveSession $session)
    {
        $this->authorize('manageAttendance', $session);

        $learners = $session->batch->enrollments()
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->pluck('user');

        return UserResource::collection($learners);
    }

    public function store(BulkAttendanceRequest $request, LiveSession $session)
    {
        $this->authorize('manageAttendance', $session);

        $records = $this->attendance->bulkUpsert($session, $request->validated('records'), $request->user());

        return AttendanceResource::collection($records->load('user'));
    }

    public function mine(Request $request)
    {
        $this->authorize('viewOwn', Attendance::class);

        $records = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->with(['session.batch'])
            ->latest('created_at')
            ->paginate(20);

        return AttendanceResource::collection($records);
    }
}
