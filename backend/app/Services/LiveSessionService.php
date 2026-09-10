<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\LiveSession;

class LiveSessionService
{
    public function create(Batch $batch, array $data): LiveSession
    {
        return $batch->liveSessions()->create($data);
    }

    public function update(LiveSession $session, array $data): LiveSession
    {
        $session->update($data);

        return $session;
    }

    public function delete(LiveSession $session): void
    {
        $session->delete();
    }
}
