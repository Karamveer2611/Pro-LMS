<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use App\Support\Slug;

class CourseService
{
    public function create(array $data, User $creator): Course
    {
        $course = Course::create([
            ...$data,
            'slug' => Slug::unique('courses', $data['title']),
            'created_by' => $creator->id,
            'status' => CourseStatus::Draft,
        ]);

        if (! empty($data['category_ids'])) {
            $course->categories()->sync($data['category_ids']);
        }

        return $course;
    }

    public function update(Course $course, array $data): Course
    {
        $course->update($data);

        if (array_key_exists('category_ids', $data)) {
            $course->categories()->sync($data['category_ids'] ?? []);
        }

        return $course;
    }

    public function setStatus(Course $course, CourseStatus $status): Course
    {
        $course->update(['status' => $status]);

        return $course;
    }

    public function delete(Course $course): void
    {
        $course->delete();
    }
}
