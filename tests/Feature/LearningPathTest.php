<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPathTest extends TestCase
{
    use RefreshDatabase;

    private User   $student;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create();
        $this->student->assignRole('student');
        $this->token = $this->student->createToken('test')->plainTextToken;
    }

    public function test_public_learning_paths_list_is_accessible_without_auth(): void
    {
        LearningPath::factory(3)->create(['status' => 'published']);

        $this->getJson('/api/v1/learning-paths')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_student_can_enroll_in_published_path(): void
    {
        $path = LearningPath::factory()->create(['status' => 'published']);

        $this->withToken($this->token)
            ->postJson("/api/v1/learning-paths/{$path->id}/enroll")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('learning_path_enrollments', [
            'user_id'          => $this->student->id,
            'learning_path_id' => $path->id,
        ]);
    }

    public function test_student_cannot_enroll_twice(): void
    {
        $path = LearningPath::factory()->create(['status' => 'published']);

        // First enroll
        $this->withToken($this->token)
            ->postJson("/api/v1/learning-paths/{$path->id}/enroll")
            ->assertOk();

        // Second attempt should fail with 422
        $this->withToken($this->token)
            ->postJson("/api/v1/learning-paths/{$path->id}/enroll")
            ->assertStatus(422);
    }

    public function test_draft_path_is_not_publicly_visible(): void
    {
        $path = LearningPath::factory()->create(['status' => 'draft']);

        $this->getJson('/api/v1/learning-paths')
            ->assertOk();

        // The draft path should NOT appear in public listing
        $this->getJson("/api/v1/learning-paths/{$path->slug}")
            ->assertStatus(404);
    }

    public function test_my_paths_requires_authentication(): void
    {
        $this->getJson('/api/v1/student/learning-paths')
            ->assertStatus(401);
    }

    public function test_enrolled_path_appears_in_my_paths(): void
    {
        $path = LearningPath::factory()->create(['status' => 'published']);
        $this->withToken($this->token)
            ->postJson("/api/v1/learning-paths/{$path->id}/enroll");

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/student/learning-paths')
            ->assertOk();

        $ids = collect($response->json('data.data') ?? $response->json('data'))
            ->pluck('learning_path_id');

        $this->assertContains($path->id, $ids->toArray());
    }
}
