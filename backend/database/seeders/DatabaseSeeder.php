<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Pro-LMS Admin',
            'email' => 'admin@poshprofstrainers.test',
        ]);

        User::factory()->instructor()->create([
            'name' => 'Demo Instructor',
            'email' => 'instructor@poshprofstrainers.test',
        ]);

        User::factory()->learner()->create([
            'name' => 'Demo Learner',
            'email' => 'learner@poshprofstrainers.test',
        ]);
    }
}
