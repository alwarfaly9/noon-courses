<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Models\UserStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create();
        $this->student->assignRole('student');
        $this->token = $this->student->createToken('test')->plainTextToken;
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/student/dashboard')
            ->assertStatus(401);
    }

    public function test_authenticated_student_can_access_dashboard(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/v1/student/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'continue_learning',
                    'progress' => ['xp_total', 'level', 'current_streak_days'],
                    'daily_goal' => ['xp_target', 'xp_earned_today'],
                    'recommendations',
                    'community_activity',
                    'motivational_card' => ['type', 'title', 'message'],
                ],
            ]);
    }

    public function test_continue_learning_shows_in_progress_courses(): void
    {
        $course = Course::factory()->create(['status' => 'published']);
        CourseEnrollment::factory()->create([
            'student_id'          => $this->student->id,
            'course_id'           => $course->id,
            'progress_percentage' => 40,
            'completed_at'        => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/student/dashboard')
            ->assertOk();

        $this->assertNotEmpty($response->json('data.continue_learning'));
        $this->assertEquals(
            $course->id,
            $response->json('data.continue_learning.0.course_id')
        );
    }

    public function test_student_can_update_daily_goals(): void
    {
        $this->withToken($this->token)
            ->patchJson('/api/v1/student/dashboard/goals', [
                'xp_target'      => 100,
                'lessons_target' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_daily_goals', [
            'user_id'        => $this->student->id,
            'xp_target'      => 100,
            'lessons_target' => 3,
        ]);
    }

    public function test_daily_goal_validation_rejects_invalid_values(): void
    {
        $this->withToken($this->token)
            ->patchJson('/api/v1/student/dashboard/goals', [
                'xp_target' => -10,
            ])
            ->assertStatus(422);
    }

    public function test_notification_preferences_endpoint_works(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/v1/student/notification-preferences')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['push_enabled', 'streak_reminders', 'quiet_hour_start'],
            ]);
    }

    public function test_notification_preferences_can_be_updated(): void
    {
        $this->withToken($this->token)
            ->patchJson('/api/v1/student/notification-preferences', [
                'streak_reminders' => false,
                'quiet_hour_start' => 22,
                'quiet_hour_end'   => 8,
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id'          => $this->student->id,
            'streak_reminders' => 0,
            'quiet_hour_start' => 22,
        ]);
    }
}
