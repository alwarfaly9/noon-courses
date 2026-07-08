<?php

namespace Tests\Feature\Engagement;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChallengeTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $this->student = User::factory()->create();
        $this->student->assignRole('student');
    }

    public function test_student_can_join_active_challenge(): void
    {
        $campaign = Campaign::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/v1/campaigns/{$campaign->id}/join");

        $response->assertOk();
        $this->assertDatabaseHas('campaign_participations', [
            'user_id'     => $this->student->id,
            'campaign_id' => $campaign->id,
        ]);
    }

    public function test_student_cannot_join_inactive_challenge(): void
    {
        $campaign = Campaign::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/v1/campaigns/{$campaign->id}/join");

        $response->assertStatus(422);
    }

    public function test_student_cannot_join_same_challenge_twice(): void
    {
        $campaign = Campaign::factory()->create(['is_active' => true]);

        $this->actingAs($this->student)
            ->postJson("/api/v1/campaigns/{$campaign->id}/join");

        $response = $this->actingAs($this->student)
            ->postJson("/api/v1/campaigns/{$campaign->id}/join");

        $response->assertOk();
        $this->assertEquals('Already participating', $response->json('message'));
    }

    public function test_active_challenges_appear_in_list(): void
    {
        Campaign::factory()->create(['is_active' => true]);
        Campaign::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->student)
            ->getJson('/api/v1/campaigns');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
