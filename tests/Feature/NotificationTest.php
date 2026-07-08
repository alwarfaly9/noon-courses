<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_notification_job_is_queued(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        dispatch(new SendNotificationJob(
            $user,
            'Test Title',
            'Test Message',
            'test'
        ));

        Queue::assertPushed(SendNotificationJob::class);
    }

    public function test_notification_service_creates_database_record(): void
    {
        $user = User::factory()->create();

        NotificationService::send($user, 'Test', 'Message body', 'test');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title'   => 'Test',
            'message' => 'Message body',
            'type'    => 'test',
            'is_read' => false,
        ]);
    }

    public function test_device_token_registration(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/device-tokens', [
                'token'    => 'fcm-token-12345',
                'platform' => 'android',
            ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_tokens', [
            'user_id'  => $user->id,
            'token'    => 'fcm-token-12345',
            'platform' => 'android',
            'is_active' => true,
        ]);
    }

    public function test_device_token_unregistration(): void
    {
        $user = User::factory()->create();
        DeviceToken::create([
            'user_id' => $user->id,
            'token'   => 'fcm-token-xyz',
            'platform' => 'ios',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/device-tokens', [
                'token' => 'fcm-token-xyz',
            ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'fcm-token-xyz',
        ]);
    }
}
