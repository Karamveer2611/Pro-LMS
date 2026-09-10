<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'session_id' => LiveSession::factory(),
            'user_id' => User::factory()->learner(),
            'status' => 'present',
            'marked_by' => User::factory()->admin(),
        ];
    }
}
