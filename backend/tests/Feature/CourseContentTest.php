<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_module_to_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/courses/{$course->id}/modules", [
            'title' => 'Module 1: Foundations',
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Module 1: Foundations');
        $this->assertDatabaseHas('modules', ['course_id' => $course->id, 'title' => 'Module 1: Foundations']);
    }

    public function test_a_non_admin_cannot_add_a_module(): void
    {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create();

        $this->actingAs($instructor)
            ->postJson("/api/v1/courses/{$course->id}/modules", ['title' => 'Nope'])
            ->assertForbidden();
    }

    public function test_new_modules_are_appended_to_the_end_by_default(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        Module::factory()->create(['course_id' => $course->id, 'order' => 0]);
        Module::factory()->create(['course_id' => $course->id, 'order' => 1]);

        $response = $this->actingAs($admin)->postJson("/api/v1/courses/{$course->id}/modules", [
            'title' => 'Third module',
        ]);

        $response->assertJsonPath('data.order', 2);
    }

    public function test_admin_can_reorder_modules(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $first = Module::factory()->create(['course_id' => $course->id, 'order' => 0]);
        $second = Module::factory()->create(['course_id' => $course->id, 'order' => 1]);

        $this->actingAs($admin)
            ->postJson("/api/v1/courses/{$course->id}/modules/reorder", [
                'ids' => [$second->id, $first->id],
            ])
            ->assertNoContent();

        $this->assertSame(0, $second->fresh()->order);
        $this->assertSame(1, $first->fresh()->order);
    }

    public function test_reordering_rejects_a_module_that_belongs_to_a_different_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $ownModule = Module::factory()->create(['course_id' => $course->id, 'order' => 0]);
        $otherCourseModule = Module::factory()->create(); // different course

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/courses/{$course->id}/modules/reorder", [
                'ids' => [$otherCourseModule->id, $ownModule->id],
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('ids');
    }

    public function test_reordering_rejects_an_incomplete_id_list(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        Module::factory()->create(['course_id' => $course->id, 'order' => 0]);
        Module::factory()->create(['course_id' => $course->id, 'order' => 1]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/courses/{$course->id}/modules/reorder", ['ids' => [999]]);

        $response->assertUnprocessable()->assertJsonValidationErrors('ids');
    }

    public function test_admin_can_add_a_section_and_a_lesson(): void
    {
        $admin = User::factory()->admin()->create();
        $module = Module::factory()->create();

        $section = $this->actingAs($admin)->postJson("/api/v1/modules/{$module->id}/sections", [
            'title' => 'Section 1',
        ])->assertCreated()->json('data');

        $this->actingAs($admin)->postJson("/api/v1/sections/{$section['id']}/lessons", [
            'title' => 'Lesson 1',
            'type' => 'text',
            'content_body' => 'Hello learners.',
        ])->assertCreated()->assertJsonPath('data.title', 'Lesson 1');
    }

    public function test_a_video_lesson_requires_a_media_id(): void
    {
        $admin = User::factory()->admin()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/sections/{$section->id}/lessons", [
            'title' => 'Intro video',
            'type' => 'video',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('media_id');
    }

    public function test_a_text_lesson_requires_content_body(): void
    {
        $admin = User::factory()->admin()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/sections/{$section->id}/lessons", [
            'title' => 'Notes',
            'type' => 'text',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('content_body');
    }

    public function test_a_days_after_enrollment_release_requires_release_days(): void
    {
        $admin = User::factory()->admin()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/v1/sections/{$section->id}/lessons", [
            'title' => 'Drip lesson',
            'type' => 'text',
            'content_body' => 'Later.',
            'release_type' => 'days_after_enrollment',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('release_days');
    }

    public function test_admin_can_delete_a_module_cascades_its_sections_and_lessons(): void
    {
        $admin = User::factory()->admin()->create();
        $module = Module::factory()->create();
        $section = Section::factory()->create(['module_id' => $module->id]);
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);

        $this->actingAs($admin)->deleteJson("/api/v1/modules/{$module->id}")->assertNoContent();

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_curriculum_endpoint_hides_unpublished_content_from_guests(): void
    {
        $course = Course::factory()->published()->create();
        $module = Module::factory()->create(['course_id' => $course->id, 'is_published' => true]);
        $section = Section::factory()->create(['module_id' => $module->id, 'is_published' => true]);
        Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Visible', 'is_published' => true]);
        Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Hidden draft lesson', 'is_published' => false]);

        $response = $this->getJson("/api/v1/courses/{$course->id}/curriculum");

        $lessonTitles = collect($response->json('data.modules.0.sections.0.lessons'))->pluck('title');
        $this->assertTrue($lessonTitles->contains('Visible'));
        $this->assertFalse($lessonTitles->contains('Hidden draft lesson'));
    }

    public function test_curriculum_endpoint_shows_unpublished_content_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->published()->create();
        $module = Module::factory()->create(['course_id' => $course->id, 'is_published' => true]);
        $section = Section::factory()->create(['module_id' => $module->id, 'is_published' => true]);
        Lesson::factory()->create(['section_id' => $section->id, 'title' => 'Hidden draft lesson', 'is_published' => false]);

        $response = $this->actingAs($admin)->getJson("/api/v1/courses/{$course->id}/curriculum");

        $lessonTitles = collect($response->json('data.modules.0.sections.0.lessons'))->pluck('title');
        $this->assertTrue($lessonTitles->contains('Hidden draft lesson'));
    }
}
