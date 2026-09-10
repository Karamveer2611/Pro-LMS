<?php

namespace Database\Factories;

use App\Enums\LessonReleaseType;
use App\Enums\LessonType;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => fake()->sentence(3),
            'type' => LessonType::Text,
            'content_body' => fake()->paragraph(),
            'order' => 0,
            'is_published' => false,
            'is_preview' => false,
            'is_required' => true,
            'release_type' => LessonReleaseType::Immediate,
        ];
    }

    public function preview(): static
    {
        return $this->state(fn () => ['is_preview' => true]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }
}
