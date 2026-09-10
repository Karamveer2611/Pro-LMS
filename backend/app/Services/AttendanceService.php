<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Single authoritative place attendance is written — the bulk-upsert shape
 * matches the trainer's "mark the whole roster, save once" flow
 * (docs/phase0-architecture.md §8.1), and every change to an already-saved
 * record is audit-logged here, not left to callers to remember.
 */
class AttendanceService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<int, array{user_id: int, status: string, notes?: ?string}>  $records
     * @return Collection<int, Attendance>
     */
    public function bulkUpsert(LiveSession $session, array $records, User $marker): Collection
    {
        return DB::transaction(function () use ($session, $records, $marker) {
            $results = new Collection;

            foreach ($records as $record) {
                $existing = Attendance::query()
                    ->where('session_id', $session->id)
                    ->where('user_id', $record['user_id'])
                    ->first();

                $attendance = Attendance::updateOrCreate(
                    ['session_id' => $session->id, 'user_id' => $record['user_id']],
                    [
                        'status' => $record['status'],
                        'notes' => $record['notes'] ?? null,
                        'marked_by' => $marker->id,
                    ],
                );

                if ($existing && $existing->status->value !== $record['status']) {
                    $this->audit->log($marker, 'attendance.status_changed', $attendance, [
                        'from' => $existing->status->value,
                        'to' => $record['status'],
                        'session_id' => $session->id,
                        'learner_id' => $record['user_id'],
                    ]);
                }

                $results->push($attendance);
            }

            return $results;
        });
    }
}
