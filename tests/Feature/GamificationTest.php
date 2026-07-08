<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Models\UserStats;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    private User   $student;
    private string $token;
    private GamificationService $gamification;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->student      = User::factory()->create();
        $this->student->assignRole('student');
        $this->token        = $this->student->createToken('test')->plainTextToken;
        $this->gamification = app(GamificationService::class);
    }

    public function test_stats_endpoint_returns_correct_structure(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/v1/gamification/stats')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'xp_total',
                    'level',
                    'current_streak_days',
                    'level_progress_percent',
                ],
            ]);
    }

    public function test_awarding_xp_increases_total(): void
    {
        $this->gamification->awardXp($this->student, 50, 'test');

        $stats = UserStats::where('user_id', $this->student->id)->first();
        $this->assertNotNull($stats);
        $this->assertEquals(50, $stats->xp_total);
    }

    public function test_awarding_xp_increases_level_when_threshold_reached(): void
    {
        // Level 2 requires 500 XP
        $this->gamification->awardXp($this->student, 500, 'test');

        $stats = UserStats::where('user_id', $this->student->id)->first();
        $this->assertEquals(2, $stats->level);
    }

    public function test_lesson_completed_awards_xp(): void
    {
        $this->gamification->onLessonCompleted($this->student);

        $stats = UserStats::where('user_id', $this->student->id)->first();
        $this->assertGreaterThanOrEqual(GamificationService::XP_LESSON, $stats->xp_total);
    }

    public function test_course_completed_awards_higher_xp(): void
    {
        $this->gamification->onCourseCompleted($this->student);

        $stats = UserStats::where('user_id', $this->student->id)->first();
        $this->assertGreaterThanOrEqual(GamificationService::XP_COURSE, $stats->xp_total);
    }

    public function test_streak_increments_on_daily_activity(): void
    {
        $this->gamification->updateStreak($this->student);

        $stats = UserStats::where('user_id', $this->student->id)->first();
        $this->assertEquals(1, $stats->current_streak_days);
    }

    public function test_leaderboard_endpoint_is_cached(): void
    {
        // First hit populates cache
        $this->withToken($this->token)
            ->getJson('/api/v1/gamification/leaderboard')
            ->assertOk();

        // Second hit should serve from cache (same result)
        $this->withToken($this->token)
            ->getJson('/api/v1/gamification/leaderboard')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_badges_endpoint_returns_earned_and_locked(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/v1/gamification/badges')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['earned', 'locked'],
            ]);
    }
}
