<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BulkAttendanceRequest;
use App\Http\Resources\AttendanceResource;
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
