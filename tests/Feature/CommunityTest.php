<?php

namespace Tests\Feature;

use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_post_comment(): void
    {
        $user   = User::factory()->create();
        $lesson = CourseLesson::factory()->create();
        // Enroll user so the enrollment check passes
        CourseEnrollment::factory()->create([
            'student_id' => $user->id,
            'course_id'  => $lesson->course_id,
        ]);
        $payload = ['content' => 'Great lesson!'];

        $response = $this->actingAs($user)
            ->postJson("/api/v1/lessons/{$lesson->id}/comments", $payload);

        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $user    = User::factory()->create();
        $comment = LessonComment::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200);
        // Table uses soft deletes
        $this->assertSoftDeleted('lesson_comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        $owner    = User::factory()->create();
        $attacker = User::factory()->create();
        $comment  = LessonComment::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker)
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(403);
    }

    public function test_report_route_is_rate_limited(): void
    {
        $user    = User::factory()->create();
        $comment = LessonComment::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $response = $this->actingAs($user)
                ->postJson("/api/v1/comments/{$comment->id}/report");
        }

        $response->assertStatus(429);
    }

    public function test_admin_can_approve_comment(): void
    {
        $admin   = $this->createUserWithRole('admin');
        $comment = LessonComment::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/comments/{$comment->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_comments', ['id' => $comment->id, 'is_approved' => true]);
    }
}
