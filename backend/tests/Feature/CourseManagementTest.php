<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_only_see_published_courses(): void
    {
        Course::factory()->published()->create(['title' => 'Published Course']);
        Course::factory()->create(['title' => 'Draft Course']); // draft by default

        $response = $this->getJson('/api/v1/courses');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Published Course'));
        $this->assertFalse($titles->contains('Draft Course'));
    }

    public function test_admin_sees_draft_courses_too(): void
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->create(['title' => 'Draft Course']);

        $response = $this->actingAs($admin)->getJson('/api/v1/courses');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Draft Course'));
    }

    public function test_a_guest_cannot_view_a_draft_courses_detail_page(): void
    {
        $course = Course::factory()->create();

        $this->getJson("/api/v1/courses/{$course->id}")->assertForbidden();
    }

    public function test_a_guest_can_view_a_published_courses_detail_page(): void
    {
        $course = Course::factory()->published()->create();

        $this->getJson("/api/v1/courses/{$course->id}")->assertOk();
    }

    public function test_admin_can_create_a_course(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/courses', [
            'title' => 'Advanced POSH Facilitation',
            'format' => 'self_paced',
            'price' => 4999,
            'default_access_days' => 90,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Advanced POSH Facilitation')
            ->assertJsonPath('data.status', 'draft')
            // Regression: the create() response must reflect the DB-level
            // currency default, not the in-memory pre-insert null.
            ->assertJsonPath('data.currency', 'INR');

        $this->assertDatabaseHas('courses', [
            'title' => 'Advanced POSH Facilitation',
            'slug' => 'advanced-posh-facilitation',
            'created_by' => $admin->id,
        ]);
    }

    public function test_a_non_admin_cannot_create_a_course(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)->postJson('/api/v1/courses', [
            'title' => 'Should Not Exist',
            'format' => 'self_paced',
        ])->assertForbidden();
    }

    public function test_creating_a_course_with_a_duplicate_title_gets_a_disambiguated_slug(): void
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->create(['title' => 'POSH Basics', 'slug' => 'posh-basics']);

        $response = $this->actingAs($admin)->postJson('/api/v1/courses', [
            'title' => 'POSH Basics',
            'format' => 'self_paced',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('courses', ['slug' => 'posh-basics-2']);
    }

    public function test_discount_price_cannot_exceed_the_regular_price(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/courses', [
            'title' => 'Bad Pricing',
            'format' => 'self_paced',
            'price' => 1000,
            'discount_price' => 1500,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('discount_price');
    }

    public function test_admin_can_publish_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->patchJson("/api/v1/courses/{$course->id}/status", [
            'status' => 'published',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'published');
        $this->assertSame(CourseStatus::Published, $course->fresh()->status);
    }

    public function test_a_non_admin_cannot_publish_a_course(): void
    {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create();

        $this->actingAs($instructor)
            ->patchJson("/api/v1/courses/{$course->id}/status", ['status' => 'published'])
            ->assertForbidden();
    }

    public function test_admin_can_soft_delete_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/courses/{$course->id}")->assertNoContent();

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_a_course_can_be_assigned_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/courses', [
            'title' => 'Categorized Course',
            'format' => 'self_paced',
            'category_ids' => [$category->id],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('course_category', ['category_id' => $category->id]);
    }
}
