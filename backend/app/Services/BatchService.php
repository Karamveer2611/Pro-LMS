<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Course;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BatchService
{
    public function create(Course $course, array $data): Batch
    {
        return $course->batches()->create($data);
    }

    public function update(Batch $batch, array $data): Batch
    {
        $batch->update($data);

        return $batch;
    }

    public function delete(Batch $batch): void
    {
        $batch->delete();
    }

    public function assignInstructor(Batch $batch, User $instructor): void
    {
        if (! $instructor->isInstructor()) {
            throw ValidationException::withMessages([
                'user_id' => ['Only users with the instructor role can be assigned to a batch.'],
            ]);
        }

        $batch->instructors()->syncWithoutDetaching([$instructor->id]);
    }

    public function unassignInstructor(Batch $batch, User $instructor): void
    {
        $batch->instructors()->detach($instructor->id);
    }
}
