<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sessions\StoreLiveSessionRequest;
use App\Http\Requests\Sessions\UpdateLiveSessionRequest;
use App\Http\Resources\LiveSessionResource;
use App\Models\Batch;
use App\Models\LiveSession;
use App\Services\LiveSessionService;
use Illuminate\Http\Request;

class LiveSessionController extends Controller
{
    public function __construct(private readonly LiveSessionService $sessions) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LiveSession::class);

        $query = LiveSession::query()->with(['batch', 'instructor']);

        if (! $request->user()->isAdmin()) {
            $query->where('instructor_id', $request->user()->id);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->integer('batch_id'));
        }

        return LiveSessionResource::collection($query->orderBy('scheduled_at')->paginate(20));
    }

    public function show(LiveSession $session): LiveSessionResource
    {
        $this->authorize('view', $session);

        return new LiveSessionResource($session->load(['batch', 'instructor']));
    }

    public function store(StoreLiveSessionRequest $request, Batch $batch): LiveSessionResource
    {
        $this->authorize('create', LiveSession::class);

        $session = $this->sessions->create($batch, $request->validated());

        return new LiveSessionResource($session->load(['batch', 'instructor']));
    }

    public function update(UpdateLiveSessionRequest $request, LiveSession $session): LiveSessionResource
    {
        $this->authorize('update', LiveSession::class);

        $session = $this->sessions->update($session, $request->validated());

        return new LiveSessionResource($session);
    }

    public function destroy(LiveSession $session)
    {
        $this->authorize('delete', LiveSession::class);

        $this->sessions->delete($session);

        return response()->json(status: 204);
    }
}
