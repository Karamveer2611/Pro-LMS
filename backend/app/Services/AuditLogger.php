<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Single authoritative place admin-action audit entries are written —
 * see docs/phase0-architecture.md's security checklist (§19). Any future
 * "this needs an audit trail" requirement calls this, not a bespoke log.
 */
class AuditLogger
{
    public function log(?User $actor, string $action, Model $entity, array $changes = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'changes' => $changes,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
