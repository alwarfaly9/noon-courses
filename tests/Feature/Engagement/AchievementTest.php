<?php

namespace Tests\Feature\Engagement;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private GamificationService $gamification;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('student');
        $this->gamification = app(GamificationService::class);
    }

    public function test_lesson_completion_triggers_badge_check(): void
    {
        Badge::factory()->create([
            'condition_type'  => 'lessons_completed',
            'condition_value' => 1,
            'is_active'       => true,
        ]);

        $this->gamification->onLessonCompleted($this->user);

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_duplicate_achievement_cannot_be_awarded(): void
    {
        $badge = Badge::factory()->create([
            'condition_type'  => 'lessons_completed',
            'condition_value' => 1,
            'is_active'       => true,
        ]);

        // First trigger awards the badge
        $this->gamification->onLessonCompleted($this->user);
        $this->assertEquals(1, UserBadge::where('user_id', $this->user->id)->count());

        // Second trigger should not duplicate
        $this->gamification->onLessonCompleted($this->user);
        $this->assertEquals(1, UserBadge::where('user_id', $this->user->id)->count());
    }

    public function test_badge_xp_is_awarded_with_badge(): void
    {
        Badge::factory()->create([
            'condition_type'  => 'lessons_completed',
            'condition_value' => 1,
            'xp_reward'       => 50,
            'is_active'       => true,
        ]);

        $this->gamification->onLessonCompleted($this->user);

        $stats = \App\Models\UserStats::where('user_id', $this->user->id)->first();
        $this->assertGreaterThanOrEqual(50, $stats->xp_total);
    }

    public function test_inactive_badges_are_not_awarded(): void
    {
        Badge::factory()->create([
            'condition_type'  => 'lessons_completed',
            'condition_value' => 1,
            'is_active'       => false,
        ]);

        $this->gamification->onLessonCompleted($this->user);

        $this->assertDatabaseMissing('user_badges', ['user_id' => $this->user->id]);
    }

    public function test_stats_endpoint_returns_correct_data(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/gamification/stats')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['xp_total', 'level', 'current_streak_days', 'lessons_completed'],
            ]);
    }
}
