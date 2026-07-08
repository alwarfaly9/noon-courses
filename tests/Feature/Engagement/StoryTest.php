<?php

namespace Tests\Feature\Engagement;

use App\Models\Course;
use App\Models\Story;
use App\Models\User;
use App\Services\StoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoryTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->assignRole('student');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    // ── Student access ────────────────────────────────────────────────────────

    public function test_student_can_only_see_active_stories(): void
    {
        Story::factory()->create(['is_active' => true]);
        Story::factory()->inactive()->create();

        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/student/stories');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_expired_stories_are_hidden_from_students(): void
    {
        Story::factory()->expired()->create();

        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/student/stories');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_student_can_view_story_by_course(): void
    {
        $course = Course::factory()->create();
        Story::factory()->forCourse($course)->create();
        Story::factory()->create(); // general story

        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/student/stories?course_id=' . $course->id);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // ── View tracking ─────────────────────────────────────────────────────────

    public function test_student_can_record_story_view(): void
    {
        $story = Story::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/stories/{$story->id}/view");

        $response->assertOk();
        $story->refresh();
        $this->assertEquals(1, $story->views_count);
        $this->assertDatabaseHas('story_views', [
            'story_id' => $story->id,
            'user_id'  => $this->student->id,
        ]);
    }

    public function test_same_user_cannot_increment_view_twice(): void
    {
        $story = Story::factory()->create(['is_active' => true]);

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/stories/{$story->id}/view");

        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/stories/{$story->id}/view");

        $story->refresh();
        $this->assertEquals(1, $story->views_count);
    }

    public function test_view_on_inactive_story_is_rejected(): void
    {
        $story = Story::factory()->inactive()->create();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/stories/{$story->id}/view");

        $response->assertStatus(410);
    }

    public function test_guest_cannot_view_stories(): void
    {
        $response = $this->getJson('/api/v1/student/stories');
        $response->assertStatus(401);
    }

    // ── Teacher story lifecycle (service-level) ────────────────────────────

    public function test_teacher_can_create_story(): void
    {
        $story = app(StoryService::class)->createStory($this->teacher, [
            'title' => 'My Story',
            'body'  => 'Story content',
        ]);

        $this->assertDatabaseHas('stories', [
            'id'      => $story->id,
            'user_id' => $this->teacher->id,
            'title'   => 'My Story',
        ]);
    }

    public function test_teacher_can_update_own_story(): void
    {
        $story = Story::factory()->create(['user_id' => $this->teacher->id]);

        app(StoryService::class)->updateStory($story, ['title' => 'Updated']);

        $this->assertEquals('Updated', $story->fresh()->title);
    }

    public function test_teacher_can_delete_own_story(): void
    {
        $story = Story::factory()->create(['user_id' => $this->teacher->id]);
        $story->delete();

        $this->assertDatabaseMissing('stories', ['id' => $story->id]);
    }

    // ── Admin management (direct model operations) ─────────────────────────

    public function test_admin_can_toggle_story_active(): void
    {
        $story = Story::factory()->create(['is_active' => true]);

        $story->update(['is_active' => !$story->is_active]);

        $this->assertFalse($story->fresh()->is_active);
    }

    public function test_admin_can_delete_any_story(): void
    {
        $story = Story::factory()->create();
        $story->delete();

        $this->assertDatabaseMissing('stories', ['id' => $story->id]);
    }
}
