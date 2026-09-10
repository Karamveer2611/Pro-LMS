<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * V1 content-gating: preview lessons are open to everyone, everything else
 * is admin-only until Week 4 wires up real enrollment access. These tests
 * exist specifically so that wiring doesn't silently loosen this gate.
 */
class LessonContentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_view_a_preview_lessons_content(): void
    {
        $lesson = Lesson::factory()->preview()->create(['content_body' => 'Free sample content.']);

        $response = $this->getJson("/api/v1/lessons/{$lesson->id}/content");

        $response->assertOk()->assertJsonPath('data.content_body', 'Free sample content.');
    }

    public function test_a_guest_cannot_view_a_non_preview_lessons_content(): void
    {
        $lesson = Lesson::factory()->create(['content_body' => 'Paid content.']);

        $this->getJson("/api/v1/lessons/{$lesson->id}/content")->assertForbidden();
    }

    public function test_a_learner_without_enrollment_cannot_view_non_preview_content(): void
    {
        $learner = User::factory()->learner()->create();
        $lesson = Lesson::factory()->create(['content_body' => 'Paid content.']);

        $this->actingAs($learner)
            ->getJson("/api/v1/lessons/{$lesson->id}/content")
            ->assertForbidden();
    }

    public function test_an_instructor_cannot_view_non_preview_content_either(): void
    {
        $instructor = User::factory()->instructor()->create();
        $lesson = Lesson::factory()->create();

        $this->actingAs($instructor)
            ->getJson("/api/v1/lessons/{$lesson->id}/content")
            ->assertForbidden();
    }

    public function test_admin_can_view_any_lessons_content(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create(['content_body' => 'Paid content.']);

        $this->actingAs($admin)
            ->getJson("/api/v1/lessons/{$lesson->id}/content")
            ->assertOk()
            ->assertJsonPath('data.content_body', 'Paid content.');
    }
}
