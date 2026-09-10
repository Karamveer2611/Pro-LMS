<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lazy checks on access (EnrollmentAccessService::activeEnrollment) already
// enforce expiry in real time; this daily sweep just keeps `status` itself
// accurate for reporting/listing without anyone needing to view the course.
Schedule::command('enrollments:sweep-expired')->daily();
