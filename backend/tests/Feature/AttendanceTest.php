<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function sessionWithEnrolledLearner(): array
    {
        $instructor = User::factory()->instructor()->create();
        $batch = Batch::factory()->create();
        $batch->instructors()->attach($instructor);
        $session = LiveSession::factory()->create(['batch_id' => $batch->id, 'instructor_id' => $instructor->id]);
        $learner = User::factory()->learner()->create();
        Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $batch->course_id, 'batch_id' => $batch->id]);

        return [$instructor, $session, $learner];
    }

    public function test_the_assigned_instructor_can_mark_attendance_for_the_session(): void
    {
        [$instructor, $session, $learner] = $this->sessionWithEnrolledLearner();

        $response = $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [
                ['user_id' => $learner->id, 'status' => 'present'],
            ],
        ]);

        $response->assertOk()->assertJsonCount(1);
        $this->assertDatabaseHas('attendance', [
            'session_id' => $session->id,
            'user_id' => $learner->id,
            'status' => 'present',
            'marked_by' => $instructor->id,
        ]);
    }

    public function test_mark_all_present_then_flip_one_to_absent_in_a_single_save(): void
    {
        [$instructor, $session, $learner] = $this->sessionWithEnrolledLearner();
        $secondLearner = User::factory()->learner()->create();
        Enrollment::factory()->create(['user_id' => $secondLearner->id, 'course_id' => $session->batch->course_id, 'batch_id' => $session->batch_id]);

        $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [
                ['user_id' => $learner->id, 'status' => 'present'],
                ['user_id' => $secondLearner->id, 'status' => 'absent'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('attendance', ['user_id' => $learner->id, 'status' => 'present']);
        $this->assertDatabaseHas('attendance', ['user_id' => $secondLearner->id, 'status' => 'absent']);
    }

    public function test_a_learner_not_enrolled_in_the_batch_cannot_be_marked(): void
    {
        [$instructor, $session] = $this->sessionWithEnrolledLearner();
        $outsider = User::factory()->learner()->create(); // not enrolled in this batch

        $response = $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $outsider->id, 'status' => 'present']],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('records.0.user_id');
    }

    public function test_an_instructor_cannot_mark_attendance_for_a_session_they_are_not_assigned_to(): void
    {
        [, $session, $learner] = $this->sessionWithEnrolledLearner();
        $otherInstructor = User::factory()->instructor()->create();

        $this->actingAs($otherInstructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'present']],
        ])->assertForbidden();
    }

    public function test_a_learner_cannot_mark_attendance(): void
    {
        [, $session, $learner] = $this->sessionWithEnrolledLearner();

        $this->actingAs($learner)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'present']],
        ])->assertForbidden();
    }

    public function test_admin_can_mark_attendance_for_any_session(): void
    {
        $admin = User::factory()->admin()->create();
        [, $session, $learner] = $this->sessionWithEnrolledLearner();

        $this->actingAs($admin)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'present']],
        ])->assertOk();
    }

    public function test_resubmitting_attendance_updates_rather_than_duplicates(): void
    {
        [$instructor, $session, $learner] = $this->sessionWithEnrolledLearner();

        $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'present']],
        ]);
        $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'absent']],
        ]);

        $this->assertDatabaseCount('attendance', 1);
        $this->assertDatabaseHas('attendance', ['user_id' => $learner->id, 'status' => 'absent']);
    }

    public function test_changing_a_saved_attendance_record_writes_an_audit_log_entry(): void
    {
        [$instructor, $session, $learner] = $this->sessionWithEnrolledLearner();

        $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'present']],
        ]);
        $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'absent']],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.status_changed',
            'actor_id' => $instructor->id,
        ]);
        $log = AuditLog::where('action', 'attendance.status_changed')->first();
        $this->assertSame('present', $log->changes['from']);
        $this->assertSame('absent', $log->changes['to']);
    }

    public function test_marking_present_for_the_first_time_does_not_write_an_audit_log(): void
    {
        [$instructor, $session, $learner] = $this->sessionWithEnrolledLearner();

        $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'present']],
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_a_learner_can_see_their_own_attendance_history(): void
    {
        [$instructor, $session, $learner] = $this->sessionWithEnrolledLearner();
        $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'present']],
        ]);

        $response = $this->actingAs($learner)->getJson('/api/v1/me/attendance');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_an_instructor_can_view_the_full_roster_for_their_session(): void
    {
        [$instructor, $session, $learner] = $this->sessionWithEnrolledLearner();
        $this->actingAs($instructor)->postJson("/api/v1/sessions/{$session->id}/attendance", [
            'records' => [['user_id' => $learner->id, 'status' => 'present']],
        ]);

        $this->actingAs($instructor)->getJson("/api/v1/sessions/{$session->id}/attendance")
            ->assertOk()->assertJsonCount(1);
    }

    public function test_the_batch_roster_endpoint_returns_every_enrolled_learner_even_before_any_marking(): void
    {
        [$instructor, $session, $learner] = $this->sessionWithEnrolledLearner();

        $response = $this->actingAs($instructor)->getJson("/api/v1/sessions/{$session->id}/roster");

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $learner->id);
    }

    public function test_an_instructor_cannot_view_the_roster_for_a_session_they_are_not_assigned_to(): void
    {
        [, $session] = $this->sessionWithEnrolledLearner();
        $otherInstructor = User::factory()->instructor()->create();

        $this->actingAs($otherInstructor)->getJson("/api/v1/sessions/{$session->id}/roster")->assertForbidden();
    }
}
