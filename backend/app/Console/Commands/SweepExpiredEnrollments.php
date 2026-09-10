<?php

namespace App\Console\Commands;

use App\Services\EnrollmentAccessService;
use Illuminate\Console\Command;

class SweepExpiredEnrollments extends Command
{
    protected $signature = 'enrollments:sweep-expired';

    protected $description = 'Flip active enrollments past their expires_at to expired (see EnrollmentAccessService::sweepExpired)';

    public function handle(EnrollmentAccessService $access): int
    {
        $count = $access->sweepExpired();
        $this->info("Expired {$count} enrollment(s).");

        return self::SUCCESS;
    }
}
