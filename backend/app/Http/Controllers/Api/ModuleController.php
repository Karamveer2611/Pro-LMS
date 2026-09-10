<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ReorderRequest;
use App\Http\Requests\Content\StoreModuleRequest;
use App\Http\Requests\Content\UpdateModuleRequest;
use App\Http\Resources\ModuleResource;
use App\Models\Course;
use App\Models\Module;
use App\Services\CourseContentService;

class ModuleController extends Controller
{
    public function __construct(private readonly CourseContentService $content) {}

    public function store(StoreModuleRequest $request, Course $course): ModuleResource
    {
        $this->authorize('manageContent', $course);

        return new ModuleResource($this->content->createModule($course, $request->validated()));
    }

    public function update(UpdateModuleRequest $request, Module $module): ModuleResource
    {
        $this->authorize('manageContent', $module->course);

        return new ModuleResource($this->content->updateModule($module, $request->validated()));
    }

    public function destroy(Module $module)
    {
        $this->authorize('manageContent', $module->course);

        $this->content->deleteModule($module);

        return response()->json(status: 204);
    }

    public function reorder(ReorderRequest $request, Course $course)
    {
        $this->authorize('manageContent', $course);

        $this->content->reorderModules($course, $request->validated('ids'));

        return response()->json(status: 204);
    }
}
