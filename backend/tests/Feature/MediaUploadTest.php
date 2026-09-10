<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->create('handbook.pdf', 500, 'application/pdf');

        $response = $this->actingAs($admin)->postJson('/api/v1/media', ['file' => $file]);

        $response->assertCreated()->assertJsonPath('data.original_filename', 'handbook.pdf');
        $this->assertDatabaseHas('media_assets', ['original_filename' => 'handbook.pdf', 'uploaded_by' => $admin->id]);
    }

    public function test_a_non_admin_cannot_upload_a_file(): void
    {
        Storage::fake('public');
        $learner = User::factory()->learner()->create();
        $file = UploadedFile::fake()->create('sneaky.pdf', 100);

        $this->actingAs($learner)->postJson('/api/v1/media', ['file' => $file])->assertForbidden();
    }

    public function test_upload_rejects_a_file_that_is_too_large(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->create('huge.mp4', 512001);

        $this->actingAs($admin)->postJson('/api/v1/media', ['file' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
    }
}
