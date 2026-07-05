<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_join_active_campaign(): void
    {
        $user     = User::factory()->create();
        $campaign = Campaign::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/campaigns/{$campaign->id}/join");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('campaign_participations', [
            'user_id'     => $user->id,
            'campaign_id' => $campaign->id,
        ]);
    }

    public function test_user_cannot_join_inactive_campaign(): void
    {
        $user     = User::factory()->create();
        $campaign = Campaign::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/campaigns/{$campaign->id}/join");

        $response->assertStatus(422);
    }

    public function test_user_cannot_join_same_campaign_twice(): void
    {
        $user     = User::factory()->create();
        $campaign = Campaign::factory()->create(['is_active' => true]);

        $this->actingAs($user)->postJson("/api/v1/campaigns/{$campaign->id}/join");
        $response = $this->actingAs($user)->postJson("/api/v1/campaigns/{$campaign->id}/join");

        $response->assertStatus(422);
    }
}
