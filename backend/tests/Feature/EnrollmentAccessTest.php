<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The real enrollment-based content gate — replaces the Week 2 admin-or-
 * preview-only placeholder. See EnrollmentAccessService, the single
 * authoritative implementation these tests are pinned against.
 */
class EnrollmentAccessTest extends TestCase
{
    use RefreshDatabase;

    private function lessonIn(Course $course, array $attrs = []): Lesson
    {
        $module = Module::factory()->create(['course_id' => $course->id]);
        $section = Section::factory()->create(['module_id' => $module->id]);

        return Lesson::factory()->create(['section_id' => $section->id, ...$attrs]);
    }

    public function test_an_enrolled_learner_can_view_non_preview_content(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();
        $lesson = $this->lessonIn($course, ['content_body' => 'Real content.']);
        Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

        $this->actingAs($learner)
            ->getJson("/api/v1/lessons/{$lesson->id}/content")
            ->assertOk()
            ->assertJsonPath('data.content_body', 'Real content.');
    }

    public function test_a_learner_with_an_expired_enrollment_is_denied(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();
        $lesson = $this->lessonIn($course);
        Enrollment::factory()->expired()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

        $this->actingAs($learner)->getJson("/api/v1/lessons/{$lesson->id}/content")->assertForbidden();
    }

    public function test_a_learner_enrolled_in_a_different_course_is_denied(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();
        $otherCourse = Course::factory()->create();
        $lesson = $this->lessonIn($course);
        Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $otherCourse->id]);

        $this->actingAs($learner)->getJson("/api/v1/lessons/{$lesson->id}/content")->assertForbidden();
    }

    public function test_a_days_after_enrollment_lesson_is_locked_before_its_release_day(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();
        $lesson = $this->lessonIn($course, ['release_type' => 'days_after_enrollment', 'release_days' => 7]);
        Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $this->actingAs($learner)->getJson("/api/v1/lessons/{$lesson->id}/content")->assertForbidden();
    }

    public function test_a_days_after_enrollment_lesson_unlocks_once_its_release_day_has_passed(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();
        $lesson = $this->lessonIn($course, [
            'content_body' => 'Unlocked now.',
            'release_type' => 'days_after_enrollment',
            'release_days' => 7,
        ]);
        Enrollment::factory()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subDays(8),
        ]);

        $this->actingAs($learner)
            ->getJson("/api/v1/lessons/{$lesson->id}/content")
            ->assertOk()
            ->assertJsonPath('data.content_body', 'Unlocked now.');
    }

    public function test_a_fixed_date_lesson_is_locked_before_that_date(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();
        $lesson = $this->lessonIn($course, [
            'release_type' => 'fixed_date',
            'release_date' => now()->addWeek()->toDateString(),
        ]);
        Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

        $this->actingAs($learner)->getJson("/api/v1/lessons/{$lesson->id}/content")->assertForbidden();
    }

    public function test_curriculum_reports_unlock_state_for_the_current_learner(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->published()->create();
        $module = Module::factory()->create(['course_id' => $course->id, 'is_published' => true]);
        $section = Section::factory()->create(['module_id' => $module->id, 'is_published' => true]);
        Lesson::factory()->create([
            'section_id' => $section->id,
            'title' => 'Locked lesson',
            'is_published' => true,
            'release_type' => 'days_after_enrollment',
            'release_days' => 30,
        ]);
        Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $response = $this->actingAs($learner)->getJson("/api/v1/courses/{$course->id}/curriculum");

        $response->assertOk()->assertJsonPath('data.modules.0.sections.0.lessons.0.is_unlocked', false);
    }

    /**
     * Regression: found via live smoke testing. An admin can legitimately
     * set a course back to draft (e.g. to edit it) without that locking out
     * learners who are already enrolled — CoursePolicy::view must check
     * enrollment, not just catalog status.
     */
    public function test_an_enrolled_learner_can_still_view_a_course_the_admin_has_set_back_to_draft(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create(); // draft by default
        Enrollment::factory()->create(['user_id' => $learner->id, 'course_id' => $course->id]);

        $this->actingAs($learner)->getJson("/api/v1/courses/{$course->id}")->assertOk();
        $this->actingAs($learner)->getJson("/api/v1/courses/{$course->id}/curriculum")->assertOk();
    }

    public function test_a_learner_without_enrollment_still_cannot_view_a_draft_course(): void
    {
        $learner = User::factory()->learner()->create();
        $course = Course::factory()->create();

        $this->actingAs($learner)->getJson("/api/v1/courses/{$course->id}")->assertForbidden();
    }

    public function test_curriculum_reports_unlocked_for_a_guest_on_a_preview_lesson(): void
    {
        $course = Course::factory()->published()->create();
        $module = Module::factory()->create(['course_id' => $course->id, 'is_published' => true]);
        $section = Section::factory()->create(['module_id' => $module->id, 'is_published' => true]);
        Lesson::factory()->preview()->create(['section_id' => $section->id, 'is_published' => true]);

        $response = $this->getJson("/api/v1/courses/{$course->id}/curriculum");

        $response->assertOk()->assertJsonPath('data.modules.0.sections.0.lessons.0.is_unlocked', true);
    }
}
