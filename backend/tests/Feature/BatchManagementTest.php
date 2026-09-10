<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_batch_for_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/courses/{$course->id}/batches", [
            'name' => 'Jan 2027 Cohort',
            'start_date' => '2027-01-05',
            'end_date' => '2027-03-05',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Jan 2027 Cohort')
            // Regression: create()'s response must reflect the DB-level
            // status default, not the in-memory pre-insert null.
            ->assertJsonPath('data.status', 'upcoming');
        $this->assertDatabaseHas('batches', ['course_id' => $course->id, 'name' => 'Jan 2027 Cohort']);
    }

    public function test_a_non_admin_cannot_create_a_batch(): void
    {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create();

        $this->actingAs($instructor)->postJson("/api/v1/courses/{$course->id}/batches", [
            'name' => 'Nope',
            'start_date' => '2027-01-05',
            'end_date' => '2027-03-05',
        ])->assertForbidden();
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/courses/{$course->id}/batches", [
            'name' => 'Bad Dates',
            'start_date' => '2027-03-05',
            'end_date' => '2027-01-05',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('end_date');
    }

    public function test_updating_only_the_end_date_still_validates_against_the_existing_start_date(): void
    {
        $admin = User::factory()->admin()->create();
        $batch = Batch::factory()->create(['start_date' => '2027-02-01', 'end_date' => '2027-04-01']);

        $response = $this->actingAs($admin)->putJson("/api/v1/batches/{$batch->id}", [
            'end_date' => '2027-01-01', // before the existing start_date
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('end_date');
    }

    public function test_learner_cannot_list_batches(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)->getJson('/api/v1/batches')->assertForbidden();
    }

    public function test_admin_sees_every_batch(): void
    {
        $admin = User::factory()->admin()->create();
        Batch::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/v1/batches');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_an_instructor_only_sees_batches_they_are_assigned_to(): void
    {
        $instructor = User::factory()->instructor()->create();
        $myBatch = Batch::factory()->create();
        $myBatch->instructors()->attach($instructor);
        Batch::factory()->count(2)->create(); // not assigned to this instructor

        $response = $this->actingAs($instructor)->getJson('/api/v1/batches');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $myBatch->id);
    }

    public function test_an_instructor_cannot_view_a_batch_they_are_not_assigned_to(): void
    {
        $instructor = User::factory()->instructor()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($instructor)->getJson("/api/v1/batches/{$batch->id}")->assertForbidden();
    }

    public function test_an_instructor_can_view_a_batch_they_are_assigned_to(): void
    {
        $instructor = User::factory()->instructor()->create();
        $batch = Batch::factory()->create();
        $batch->instructors()->attach($instructor);

        $this->actingAs($instructor)->getJson("/api/v1/batches/{$batch->id}")->assertOk();
    }

    public function test_admin_can_assign_an_instructor_to_a_batch(): void
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();
        $batch = Batch::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/batches/{$batch->id}/instructors", [
            'user_id' => $instructor->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('batch_instructor', ['batch_id' => $batch->id, 'user_id' => $instructor->id]);
    }

    public function test_a_learner_cannot_be_assigned_as_an_instructor(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $batch = Batch::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/batches/{$batch->id}/instructors", [
            'user_id' => $learner->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('user_id');
    }

    public function test_an_instructor_cannot_assign_instructors_even_to_their_own_batch(): void
    {
        $instructor = User::factory()->instructor()->create();
        $otherInstructor = User::factory()->instructor()->create();
        $batch = Batch::factory()->create();
        $batch->instructors()->attach($instructor);

        $this->actingAs($instructor)->postJson("/api/v1/batches/{$batch->id}/instructors", [
            'user_id' => $otherInstructor->id,
        ])->assertForbidden();
    }

    public function test_admin_can_unassign_an_instructor(): void
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();
        $batch = Batch::factory()->create();
        $batch->instructors()->attach($instructor);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/batches/{$batch->id}/instructors/{$instructor->id}")
            ->assertOk();

        $this->assertDatabaseMissing('batch_instructor', ['batch_id' => $batch->id, 'user_id' => $instructor->id]);
    }

    public function test_batches_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Batch::factory()->create(['status' => 'ongoing']);
        Batch::factory()->create(['status' => 'completed']);

        $response = $this->actingAs($admin)->getJson('/api/v1/batches?status=ongoing');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_delete_a_batch(): void
    {
        $admin = User::factory()->admin()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/batches/{$batch->id}")->assertNoContent();
        $this->assertDatabaseMissing('batches', ['id' => $batch->id]);
    }
}
