<?php

namespace Tests\Unit\Services;

use App\Models\Story;
use App\Models\User;
use App\Services\StoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private StoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StoryService::class);
    }

    public function test_create_story_sets_views_count_to_zero(): void
    {
        $user = User::factory()->create();
        $story = $this->service->createStory($user, ['title' => 'Test']);

        $this->assertEquals(0, $story->views_count);
    }

    public function test_record_view_returns_true_for_new_view(): void
    {
        $story = Story::factory()->create();
        $user = User::factory()->create();

        $result = $this->service->recordView($story, $user);

        $this->assertTrue($result);
        $this->assertEquals(1, $story->fresh()->views_count);
    }

    public function test_record_view_returns_false_for_duplicate(): void
    {
        $story = Story::factory()->create();
        $user = User::factory()->create();

        $this->service->recordView($story, $user);
        $result = $this->service->recordView($story, $user);

        $this->assertFalse($result);
        $this->assertEquals(1, $story->fresh()->views_count);
    }

    public function test_get_active_stories_filters_expired(): void
    {
        Story::factory()->expired()->create();
        Story::factory()->create();

        $stories = $this->service->getActiveStories();

        $this->assertCount(1, $stories);
    }

    public function test_get_active_stories_filters_inactive(): void
    {
        Story::factory()->inactive()->create();
        Story::factory()->create();

        $stories = $this->service->getActiveStories();

        $this->assertCount(1, $stories);
    }

    public function test_get_view_stats_returns_correct_counts(): void
    {
        $story = Story::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->service->recordView($story, $user1);
        $this->service->recordView($story, $user2);

        $stats = $this->service->getViewStats($story);

        $this->assertEquals(2, $stats['total_views']);
        $this->assertEquals(2, $stats['unique_viewers']);
    }
}
