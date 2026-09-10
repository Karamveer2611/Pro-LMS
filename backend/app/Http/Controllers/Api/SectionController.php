<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ReorderRequest;
use App\Http\Requests\Content\StoreSectionRequest;
use App\Http\Requests\Content\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Module;
use App\Models\Section;
use App\Services\CourseContentService;

class SectionController extends Controller
{
    public function __construct(private readonly CourseContentService $content) {}

    public function store(StoreSectionRequest $request, Module $module): SectionResource
    {
        $this->authorize('manageContent', $module->course);

        return new SectionResource($this->content->createSection($module, $request->validated()));
    }

    public function update(UpdateSectionRequest $request, Section $section): SectionResource
    {
        $this->authorize('manageContent', $section->module->course);

        return new SectionResource($this->content->updateSection($section, $request->validated()));
    }

    public function destroy(Section $section)
    {
        $this->authorize('manageContent', $section->module->course);

        $this->content->deleteSection($section);

        return response()->json(status: 204);
    }

    public function reorder(ReorderRequest $request, Module $module)
    {
        $this->authorize('manageContent', $module->course);

        $this->content->reorderSections($module, $request->validated('ids'));

        return response()->json(status: 204);
    }
}
