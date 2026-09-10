<?php

namespace Database\Factories;

use App\Enums\CourseFormat;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'format' => CourseFormat::SelfPaced,
            'price' => fake()->randomFloat(2, 999, 9999),
            'default_access_days' => 90,
            'status' => CourseStatus::Draft,
            'created_by' => User::factory()->admin(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => CourseStatus::Published]);
    }
}
