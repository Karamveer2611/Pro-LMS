<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_schedule_a_session_for_a_batch(): void
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();
        $batch = Batch::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/batches/{$batch->id}/sessions", [
            'instructor_id' => $instructor->id,
            'title' => 'Week 1 Live Session',
            'scheduled_at' => now()->addDays(3)->toIso8601String(),
            'meeting_link' => 'https://meet.example.com/abc',
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Week 1 Live Session');
        $this->assertDatabaseHas('live_sessions', ['batch_id' => $batch->id, 'instructor_id' => $instructor->id]);
    }

    public function test_a_learner_cannot_be_assigned_as_a_sessions_instructor(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $batch = Batch::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/batches/{$batch->id}/sessions", [
            'instructor_id' => $learner->id,
            'title' => 'Bad Session',
            'scheduled_at' => now()->addDays(3)->toIso8601String(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('instructor_id');
    }

    public function test_a_non_admin_cannot_schedule_a_session(): void
    {
        $instructor = User::factory()->instructor()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($instructor)->postJson("/api/v1/batches/{$batch->id}/sessions", [
            'instructor_id' => $instructor->id,
            'title' => 'Nope',
            'scheduled_at' => now()->addDays(3)->toIso8601String(),
        ])->assertForbidden();
    }

    public function test_an_instructor_only_sees_sessions_they_are_assigned_to(): void
    {
        $instructor = User::factory()->instructor()->create();
        $mySession = LiveSession::factory()->create(['instructor_id' => $instructor->id]);
        LiveSession::factory()->count(2)->create(); // other instructors

        $response = $this->actingAs($instructor)->getJson('/api/v1/sessions');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mySession->id);
    }

    public function test_an_instructor_cannot_view_a_session_they_are_not_assigned_to(): void
    {
        $instructor = User::factory()->instructor()->create();
        $session = LiveSession::factory()->create();

        $this->actingAs($instructor)->getJson("/api/v1/sessions/{$session->id}")->assertForbidden();
    }

    public function test_admin_can_update_a_session(): void
    {
        $admin = User::factory()->admin()->create();
        $session = LiveSession::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/v1/sessions/{$session->id}", [
            'status' => 'completed',
            'recording_url' => 'https://example.com/recording.mp4',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'completed');
    }

    public function test_admin_can_delete_a_session(): void
    {
        $admin = User::factory()->admin()->create();
        $session = LiveSession::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/sessions/{$session->id}")->assertNoContent();
        $this->assertDatabaseMissing('live_sessions', ['id' => $session->id]);
    }
}
