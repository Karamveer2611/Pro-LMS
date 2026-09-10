<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manually_enroll_a_learner(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create(['default_access_days' => 90]);

        $response = $this->actingAs($admin)->postJson('/api/v1/enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.source', 'manual');
        $this->assertDatabaseHas('enrollments', ['user_id' => $learner->id, 'course_id' => $course->id]);
    }

    public function test_expires_at_is_computed_from_the_course_default_access_days(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create(['default_access_days' => 30]);

        $response = $this->actingAs($admin)->postJson('/api/v1/enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $enrollment = Enrollment::first();
        $this->assertNotNull($enrollment->expires_at);
        $this->assertEqualsWithDelta(
            now()->addDays(30)->timestamp,
            $enrollment->expires_at->timestamp,
            5,
        );
    }

    public function test_a_course_with_no_default_access_days_grants_lifetime_access(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create(['default_access_days' => null]);

        $this->actingAs($admin)->postJson('/api/v1/enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $this->assertNull(Enrollment::first()->expires_at);
    }

    public function test_a_batchs_access_days_override_takes_precedence_over_the_course_default(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create(['default_access_days' => 30]);
        $batch = Batch::factory()->create(['course_id' => $course->id, 'access_days_override' => 180]);

        $this->actingAs($admin)->postJson('/api/v1/enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'batch_id' => $batch->id,
        ]);

        $enrollment = Enrollment::first();
        $this->assertEqualsWithDelta(now()->addDays(180)->timestamp, $enrollment->expires_at->timestamp, 5);
    }

    public function test_a_batch_from_a_different_course_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();
        $otherCourseBatch = Batch::factory()->create(); // belongs to a different course

        $response = $this->actingAs($admin)->postJson('/api/v1/enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'batch_id' => $otherCourseBatch->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('batch_id');
    }

    public function test_only_learners_can_be_enrolled(): void
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/enrollments', [
            'user_id' => $instructor->id,
            'course_id' => $course->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('user_id');
    }

    public function test_a_non_admin_cannot_create_an_enrollment(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();

        $this->actingAs($learner)->postJson('/api/v1/enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ])->assertForbidden();
    }

    public function test_duplicate_direct_enrollment_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();
        Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id, 'batch_id' => null]);

        $response = $this->actingAs($admin)->postJson('/api/v1/enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('course_id');
    }

    public function test_a_learner_only_sees_their_own_enrollments(): void
    {
        $learner = User::factory()->learner()->create();
        Enrollment::factory()->create(['user_id' => $learner->id]);
        Enrollment::factory()->count(2)->create(); // other learners

        $response = $this->actingAs($learner)->getJson('/api/v1/enrollments');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_learner_cannot_view_another_learners_enrollment(): void
    {
        $learner = User::factory()->learner()->create();
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($learner)->getJson("/api/v1/enrollments/{$enrollment->id}")->assertForbidden();
    }

    public function test_admin_can_extend_an_enrollments_access(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->expired()->create();
        $newExpiry = now()->addDays(60)->toIso8601String();

        $response = $this->actingAs($admin)->patchJson("/api/v1/enrollments/{$enrollment->id}/expiry", [
            'expires_at' => $newExpiry,
        ]);

        $response->assertOk();
        $this->assertTrue($enrollment->fresh()->expires_at->isFuture());
    }

    public function test_a_non_admin_cannot_extend_an_enrollment(): void
    {
        $learner = User::factory()->learner()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $learner->id]);

        $this->actingAs($learner)
            ->patchJson("/api/v1/enrollments/{$enrollment->id}/expiry", ['expires_at' => now()->addDays(30)->toIso8601String()])
            ->assertForbidden();
    }

    public function test_admin_can_cancel_an_enrollment(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($admin)->postJson("/api/v1/enrollments/{$enrollment->id}/cancel")->assertOk();
        $this->assertSame('cancelled', $enrollment->fresh()->status->value);
    }

    public function test_sweep_expired_flips_status_on_past_due_enrollments(): void
    {
        $enrollment = Enrollment::factory()->create(['expires_at' => now()->subDay()]);
        $stillValid = Enrollment::factory()->create(['expires_at' => now()->addDay()]);

        app(EnrollmentAccessService::class)->sweepExpired();

        $this->assertSame('expired', $enrollment->fresh()->status->value);
        $this->assertSame('active', $stillValid->fresh()->status->value);
    }
}
