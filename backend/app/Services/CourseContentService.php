<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single authoritative service for course content structure: creating,
 * updating, deleting, and ordering modules/sections/lessons. Centralized so
 * "assign the next order value" and "reorder within a parent" are each
 * implemented exactly once.
 */
class CourseContentService
{
    // --- Modules ---------------------------------------------------------

    public function createModule(Course $course, array $data): Module
    {
        return $course->modules()->create([
            ...$data,
            'order' => $data['order'] ?? $course->modules()->count(),
        ]);
    }

    public function updateModule(Module $module, array $data): Module
    {
        $module->update($data);

        return $module;
    }

    public function deleteModule(Module $module): void
    {
        $module->delete();
    }

    public function reorderModules(Course $course, array $moduleIds): void
    {
        $this->reorder($course->modules(), $moduleIds);
    }

    // --- Sections ----------------------------------------------------------

    public function createSection(Module $module, array $data): Section
    {
        return $module->sections()->create([
            ...$data,
            'order' => $data['order'] ?? $module->sections()->count(),
        ]);
    }

    public function updateSection(Section $section, array $data): Section
    {
        $section->update($data);

        return $section;
    }

    public function deleteSection(Section $section): void
    {
        $section->delete();
    }

    public function reorderSections(Module $module, array $sectionIds): void
    {
        $this->reorder($module->sections(), $sectionIds);
    }

    // --- Lessons -------------------------------------------------------

    public function createLesson(Section $section, array $data): Lesson
    {
        return $section->lessons()->create([
            ...$data,
            'order' => $data['order'] ?? $section->lessons()->count(),
        ]);
    }

    public function updateLesson(Lesson $lesson, array $data): Lesson
    {
        $lesson->update($data);

        return $lesson;
    }

    public function deleteLesson(Lesson $lesson): void
    {
        $lesson->delete();
    }

    public function reorderLessons(Section $section, array $lessonIds): void
    {
        $this->reorder($section->lessons(), $lessonIds);
    }

    /**
     * Assign order = position in $ids to every row of $relation, after
     * verifying $ids is exactly the relation's current member set — this is
     * what stops a request from reordering rows into/out of a parent it
     * doesn't belong to.
     */
    private function reorder($relation, array $ids): void
    {
        $existingIds = $relation->pluck('id')->all();

        if (count($ids) !== count($existingIds) || array_diff($ids, $existingIds) !== []) {
            throw ValidationException::withMessages([
                'ids' => ['The given ids must exactly match this item\'s current children.'],
            ]);
        }

        DB::transaction(function () use ($relation, $ids) {
            foreach ($ids as $index => $id) {
                $relation->getRelated()->where('id', $id)->update(['order' => $index]);
            }
        });
    }
}
