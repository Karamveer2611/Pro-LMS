<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ReorderRequest;
use App\Http\Requests\Content\StoreLessonRequest;
use App\Http\Requests\Content\UpdateLessonRequest;
use App\Http\Resources\LessonContentResource;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\Section;
use App\Services\CourseContentService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(private readonly CourseContentService $content) {}

    public function store(StoreLessonRequest $request, Section $section): LessonResource
    {
        $this->authorize('manageContent', $section->module->course);

        return new LessonResource($this->content->createLesson($section, $request->validated()));
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): LessonResource
    {
        $this->authorize('manageContent', $lesson->section->module->course);

        return new LessonResource($this->content->updateLesson($lesson, $request->validated()));
    }

    public function destroy(Lesson $lesson)
    {
        $this->authorize('manageContent', $lesson->section->module->course);

        $this->content->deleteLesson($lesson);

        return response()->json(status: 204);
    }

    public function reorder(ReorderRequest $request, Section $section)
    {
        $this->authorize('manageContent', $section->module->course);

        $this->content->reorderLessons($section, $request->validated('ids'));

        return response()->json(status: 204);
    }

    public function content(Request $request, Lesson $lesson): LessonContentResource
    {
        $this->authorize('viewContent', $lesson);

        return new LessonContentResource($lesson->load('media'));
    }
}
