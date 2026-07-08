<?php

namespace Tests\Unit\Services;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserStats;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GamificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GamificationService::class);
    }

    public function test_award_xp_increases_total(): void
    {
        $user = User::factory()->create();

        $this->service->awardXp($user, 100, 'test');

        $stats = UserStats::where('user_id', $user->id)->first();
        $this->assertEquals(100, $stats->xp_total);
    }

    public function test_level_up_at_threshold(): void
    {
        $user = User::factory()->create();

        $this->service->awardXp($user, 500, 'test');

        $stats = UserStats::where('user_id', $user->id)->first();
        $this->assertEquals(2, $stats->level);
    }

    public function test_level_7_at_25000_xp(): void
    {
        $user = User::factory()->create();

        $this->service->awardXp($user, 25000, 'test');

        $stats = UserStats::where('user_id', $user->id)->first();
        $this->assertEquals(7, $stats->level);
    }

    public function test_streak_starts_at_one(): void
    {
        $user = User::factory()->create();

        $this->service->updateStreak($user);

        $stats = UserStats::where('user_id', $user->id)->first();
        $this->assertEquals(1, $stats->current_streak_days);
    }

    public function test_on_lesson_completed_increments_counter(): void
    {
        $user = User::factory()->create();

        $this->service->onLessonCompleted($user);

        $stats = UserStats::where('user_id', $user->id)->first();
        $this->assertEquals(1, $stats->lessons_completed);
    }

    public function test_on_course_completed_increments_counter(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create();

        $this->service->onCourseCompleted($user, $course);

        $stats = UserStats::where('user_id', $user->id)->first();
        $this->assertEquals(1, $stats->courses_completed);
    }

    public function test_on_quiz_passed_triggers_badge_check(): void
    {
        Badge::factory()->create([
            'condition_type'  => 'quizzes_passed',
            'condition_value' => 1,
            'is_active'       => true,
        ]);

        $user = User::factory()->create();

        $this->service->onQuizPassed($user, 85);

        $this->assertDatabaseHas('user_badges', ['user_id' => $user->id]);
    }
}
