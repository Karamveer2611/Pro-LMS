<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollments\ExtendEnrollmentRequest;
use App\Http\Requests\Enrollments\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Enrollment::class);

        $query = Enrollment::query()->with(['user', 'course']);

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return EnrollmentResource::collection($query->latest()->paginate(20));
    }

    public function show(Enrollment $enrollment): EnrollmentResource
    {
        $this->authorize('view', $enrollment);

        return new EnrollmentResource($enrollment->load(['user', 'course']));
    }

    public function store(StoreEnrollmentRequest $request): EnrollmentResource
    {
        $learner = User::findOrFail($request->validated('user_id'));
        $course = Course::findOrFail($request->validated('course_id'));
        $batch = $request->validated('batch_id') ? Batch::find($request->validated('batch_id')) : null;

        $enrollment = $this->enrollments->enroll($learner, $course, $batch, $request->user(), $request->validated('notes'));

        return new EnrollmentResource($enrollment->load(['user', 'course']));
    }

    public function extend(ExtendEnrollmentRequest $request, Enrollment $enrollment): EnrollmentResource
    {
        $this->authorize('update', Enrollment::class);

        $enrollment = $this->enrollments->extendAccess($enrollment, Carbon::parse($request->validated('expires_at')));

        return new EnrollmentResource($enrollment->load(['user', 'course']));
    }

    public function cancel(Enrollment $enrollment): EnrollmentResource
    {
        $this->authorize('update', Enrollment::class);

        $enrollment = $this->enrollments->cancel($enrollment);

        return new EnrollmentResource($enrollment->load(['user', 'course']));
    }
}
