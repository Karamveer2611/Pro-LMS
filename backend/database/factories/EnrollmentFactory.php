<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->learner(),
            'course_id' => Course::factory(),
            'enrolled_at' => now(),
            'expires_at' => now()->addDays(90),
            'enrolled_by' => User::factory()->admin(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => 'expired', 'expires_at' => now()->subDay()]);
    }

    public function lifetime(): static
    {
        return $this->state(fn () => ['expires_at' => null]);
    }
}
