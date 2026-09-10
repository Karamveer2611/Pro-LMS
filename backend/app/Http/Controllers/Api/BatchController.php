<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Batches\StoreBatchRequest;
use App\Http\Requests\Batches\UpdateBatchRequest;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\Course;
use App\Services\BatchService;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function __construct(private readonly BatchService $batches) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::query()->with(['course', 'instructors']);

        if (! $request->user()->isAdmin()) {
            $query->whereHas('instructors', fn ($q) => $q->where('users.id', $request->user()->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('instructor_id')) {
            $query->whereHas('instructors', fn ($q) => $q->where('users.id', $request->integer('instructor_id')));
        }

        return BatchResource::collection($query->latest()->paginate(20));
    }

    public function show(Batch $batch): BatchResource
    {
        $this->authorize('view', $batch);

        return new BatchResource($batch->load(['course', 'instructors']));
    }

    public function store(StoreBatchRequest $request, Course $course): BatchResource
    {
        $this->authorize('create', Batch::class);

        $batch = $this->batches->create($course, $request->validated());

        return new BatchResource($batch);
    }

    public function update(UpdateBatchRequest $request, Batch $batch): BatchResource
    {
        $this->authorize('update', $batch);

        $batch = $this->batches->update($batch, $request->validated());

        return new BatchResource($batch);
    }

    public function destroy(Batch $batch)
    {
        $this->authorize('delete', $batch);

        $this->batches->delete($batch);

        return response()->json(status: 204);
    }
}
