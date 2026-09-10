<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_categories(): void
    {
        Category::factory()->count(2)->create();

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/categories', ['name' => 'POSH Compliance']);

        $response->assertCreated()->assertJsonPath('data.slug', 'posh-compliance');
    }

    public function test_a_non_admin_cannot_create_a_category(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)
            ->postJson('/api/v1/categories', ['name' => 'Nope'])
            ->assertForbidden();
    }

    public function test_admin_can_delete_a_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/categories/{$category->id}")->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
