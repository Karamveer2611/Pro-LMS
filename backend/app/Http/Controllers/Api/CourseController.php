<?php

namespace App\Http\Controllers\Api;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\StoreCourseRequest;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Http\Requests\Courses\UpdateCourseStatusRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use App\Services\EnrollmentAccessService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly EnrollmentAccessService $access,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Course::class);

        $query = Course::query()->with(['thumbnail', 'categories'])->latest();

        if (! $request->user()?->isAdmin()) {
            $query->where('status', CourseStatus::Published);
        }

        return CourseResource::collection($query->paginate(20));
    }

    public function show(Request $request, Course $course): CourseResource
    {
        $this->authorize('view', $course);

        return new CourseResource($course->load(['thumbnail', 'categories']));
    }

    public function curriculum(Request $request, Course $course): CourseResource
    {
        $this->authorize('view', $course);

        $course->load(['modules' => function ($query) use ($request) {
            if (! $request->user()?->isAdmin()) {
                $query->where('is_published', true);
            }
        }, 'modules.sections' => function ($query) use ($request) {
            if (! $request->user()?->isAdmin()) {
                $query->where('is_published', true);
            }
        }, 'modules.sections.lessons' => function ($query) use ($request) {
            if (! $request->user()?->isAdmin()) {
                $query->where('is_published', true);
            }
        }]);

        foreach ($course->modules as $module) {
            foreach ($module->sections as $section) {
                foreach ($section->lessons as $lesson) {
                    $lesson->setAttribute('access_state', $this->access->lessonAccessState($request->user(), $lesson));
                }
            }
        }

        return new CourseResource($course);
    }

    public function store(StoreCourseRequest $request): CourseResource
    {
        $this->authorize('create', Course::class);

        $course = $this->courses->create($request->validated(), $request->user());

        return new CourseResource($course);
    }

    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $this->authorize('update', $course);

        $course = $this->courses->update($course, $request->validated());

        return new CourseResource($course);
    }

    public function updateStatus(UpdateCourseStatusRequest $request, Course $course): CourseResource
    {
        $this->authorize('update', $course);

        $course = $this->courses->setStatus($course, CourseStatus::from($request->validated('status')));

        return new CourseResource($course);
    }

    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);

        $this->courses->delete($course);

        return response()->json(status: 204);
    }
}
