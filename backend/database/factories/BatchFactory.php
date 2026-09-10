<?php

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+2 weeks');

        return [
            'course_id' => Course::factory(),
            'name' => 'Batch '.fake()->unique()->numberBetween(1, 9999),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+60 days'),
            'status' => BatchStatus::Upcoming,
        ];
    }
}
