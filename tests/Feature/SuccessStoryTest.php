<?php

namespace Tests\Feature;

use App\Models\SuccessStory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuccessStoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_story(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/success-stories', [
            'title'              => 'My success',
            'body'               => str_repeat('a', 50),
            'before_description' => 'Before',
            'after_description'  => 'After',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('success_stories', [
            'user_id'     => $user->id,
            'is_approved' => false,
        ]);
    }

    public function test_public_index_only_shows_approved_stories(): void
    {
        SuccessStory::factory()->create(['is_approved' => false]);
        SuccessStory::factory()->create(['is_approved' => true]);

        $response = $this->getJson('/api/v1/success-stories');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
    }

    public function test_admin_can_approve_story(): void
    {
        $admin = $this->createUserWithRole('admin');
        $story = SuccessStory::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/success-stories/{$story->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('success_stories', ['id' => $story->id, 'is_approved' => true]);
    }
}
