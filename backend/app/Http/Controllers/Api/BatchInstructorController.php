<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Batches\AssignInstructorRequest;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\User;
use App\Services\BatchService;

class BatchInstructorController extends Controller
{
    public function __construct(private readonly BatchService $batches) {}

    public function store(AssignInstructorRequest $request, Batch $batch): BatchResource
    {
        $this->authorize('manageInstructors', Batch::class);

        $instructor = User::findOrFail($request->validated('user_id'));
        $this->batches->assignInstructor($batch, $instructor);

        return new BatchResource($batch->load('instructors'));
    }

    public function destroy(Batch $batch, User $user): BatchResource
    {
        $this->authorize('manageInstructors', Batch::class);

        $this->batches->unassignInstructor($batch, $user);

        return new BatchResource($batch->load('instructors'));
    }
}
