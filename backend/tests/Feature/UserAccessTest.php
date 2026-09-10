<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/v1/users');

        $response->assertOk();
        $this->assertCount(4, $response->json('data'));
    }

    public function test_instructor_cannot_list_users(): void
    {
        $instructor = User::factory()->instructor()->create();

        $this->actingAs($instructor)->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_learner_cannot_list_users(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_unauthenticated_requests_cannot_list_users(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
    }

    public function test_admin_can_view_any_users_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();

        $this->actingAs($admin)->getJson("/api/v1/users/{$learner->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $learner->id);
    }

    public function test_a_learner_can_view_their_own_profile(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)->getJson("/api/v1/users/{$learner->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $learner->id);
    }

    public function test_a_learner_cannot_view_another_learners_profile(): void
    {
        $learner = User::factory()->learner()->create();
        $otherLearner = User::factory()->learner()->create();

        $this->actingAs($learner)->getJson("/api/v1/users/{$otherLearner->id}")
            ->assertForbidden();
    }

    public function test_an_instructor_cannot_view_another_users_profile(): void
    {
        $instructor = User::factory()->instructor()->create();
        $learner = User::factory()->learner()->create();

        $this->actingAs($instructor)->getJson("/api/v1/users/{$learner->id}")
            ->assertForbidden();
    }

    public function test_admin_can_create_a_user_of_any_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/users', [
            'name' => 'New Instructor',
            'email' => 'new-instructor@example.com',
            'password' => 'password123',
            'role' => 'instructor',
        ]);

        $response->assertCreated()->assertJsonPath('data.role', 'instructor');
    }

    public function test_a_non_admin_cannot_create_a_user(): void
    {
        $learner = User::factory()->learner()->create();

        $response = $this->actingAs($learner)->postJson('/api/v1/users', [
            'name' => 'New Instructor',
            'email' => 'new-instructor@example.com',
            'password' => 'password123',
            'role' => 'instructor',
        ]);

        $response->assertForbidden();
    }

    public function test_creating_a_user_rejects_an_invalid_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'role' => 'super-admin',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('role');
    }

    public function test_requesting_a_nonexistent_user_returns_not_found_not_a_leak(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/v1/users/999999')->assertNotFound();
    }
}
