<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSession>
 */
class LiveSessionFactory extends Factory
{
    protected $model = LiveSession::class;

    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'instructor_id' => User::factory()->instructor(),
            'title' => fake()->sentence(3),
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 weeks'),
        ];
    }
}
