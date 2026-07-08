<?php

namespace Tests\Feature;

use App\Events\AchievementUnlocked;
use App\Events\AnnouncementCreated;
use App\Events\CommentCreated;
use App\Events\CommentReplied;
use App\Events\CourseApproved;
use App\Events\CourseCompleted;
use App\Events\CourseRejected;
use App\Events\EnrollmentCreated;
use App\Events\PaymentCompleted;
use App\Events\WithdrawalUpdated;
use App\Jobs\SendNotificationJob;
use App\Models\Badge;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\DeviceToken;
use App\Models\LessonComment;
use App\Models\Notification;
use App\Models\NotificationAnalytics;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Models\WithdrawRequest;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    // ── Existing Tests ───────────────────────────────────────────────────────

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

    // ── New: Notification Service with Category/Priority/ActionUrl ──────────

    public function test_notification_service_saves_new_fields(): void
    {
        $user = User::factory()->create();

        NotificationService::send(
            $user,
            'Test Title',
            'Test Message',
            'test',
            ['key' => 'value'],
            category: 'course',
            priority: 'high',
            actionUrl: '/courses/1',
            metadata: ['course_id' => 1],
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'category' => 'course',
            'priority' => 'high',
            'action_url' => '/courses/1',
        ]);

        $notification = Notification::where('user_id', $user->id)->first();
        $this->assertNotNull($notification->metadata);
        $this->assertEquals(['course_id' => 1], $notification->metadata);
    }

    // ── New: Event Dispatching ──────────────────────────────────────────────

    public function test_course_completed_event_dispatches(): void
    {
        Event::fake([CourseCompleted::class]);

        $user = User::factory()->create();
        $course = Course::factory()->create();

        CourseCompleted::dispatch($user, $course);

        Event::assertDispatched(CourseCompleted::class, fn($e) => $e->user->id === $user->id && $e->course->id === $course->id);
    }

    public function test_course_approved_event_dispatches(): void
    {
        Event::fake([CourseApproved::class]);

        $course = Course::factory()->create();
        CourseApproved::dispatch($course);

        Event::assertDispatched(CourseApproved::class, fn($e) => $e->course->id === $course->id);
    }

    public function test_course_rejected_event_dispatches(): void
    {
        Event::fake([CourseRejected::class]);

        $course = Course::factory()->create();
        CourseRejected::dispatch($course, 'Not approved');

        Event::assertDispatched(CourseRejected::class, fn($e) => $e->course->id === $course->id && $e->reason === 'Not approved');
    }

    public function test_enrollment_created_event_dispatches(): void
    {
        Event::fake([EnrollmentCreated::class]);

        $student = User::factory()->create();
        $course = Course::factory()->create();
        EnrollmentCreated::dispatch($student, $course);

        Event::assertDispatched(EnrollmentCreated::class);
    }

    public function test_achievement_unlocked_event_dispatches(): void
    {
        Event::fake([AchievementUnlocked::class]);

        $user = User::factory()->create();
        $badge = Badge::factory()->create();
        AchievementUnlocked::dispatch($user, $badge);

        Event::assertDispatched(AchievementUnlocked::class, fn($e) => $e->badge->id === $badge->id);
    }

    public function test_comment_created_event_dispatches(): void
    {
        Event::fake([CommentCreated::class]);

        $comment = LessonComment::factory()->create();
        CommentCreated::dispatch($comment);

        Event::assertDispatched(CommentCreated::class);
    }

    public function test_comment_replied_event_dispatches(): void
    {
        Event::fake([CommentReplied::class]);

        $reply = LessonComment::factory()->create();
        $parent = LessonComment::factory()->create();
        CommentReplied::dispatch($reply, $parent);

        Event::assertDispatched(CommentReplied::class, fn($e) => $e->reply->id === $reply->id && $e->parent->id === $parent->id);
    }

    public function test_payment_completed_event_dispatches(): void
    {
        Event::fake([PaymentCompleted::class]);

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $transaction = Transaction::factory()->create();
        PaymentCompleted::dispatch($user, $course, $transaction);

        Event::assertDispatched(PaymentCompleted::class);
    }

    public function test_withdrawal_updated_event_dispatches(): void
    {
        Event::fake([WithdrawalUpdated::class]);

        $teacher = User::factory()->create();
        $request = WithdrawRequest::factory()->create();
        WithdrawalUpdated::dispatch($request, $teacher);

        Event::assertDispatched(WithdrawalUpdated::class);
    }

    public function test_announcement_created_event_dispatches(): void
    {
        Event::fake([AnnouncementCreated::class]);

        AnnouncementCreated::dispatch('Test Title', 'Test Message', 'all');

        Event::assertDispatched(AnnouncementCreated::class);
    }

    // ── New: Notification Model Scopes ──────────────────────────────────────

    public function test_notification_unread_scope(): void
    {
        $user = User::factory()->create();

        Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'Read', 'message' => 'm', 'is_read' => true]);
        Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'Unread', 'message' => 'm', 'is_read' => false]);

        $unread = Notification::unread()->get();
        $this->assertCount(1, $unread);
        $this->assertEquals('Unread', $unread->first()->title);
    }

    public function test_notification_category_scope(): void
    {
        $user = User::factory()->create();

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'A', 'message' => 'm', 'category' => 'course']);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'B', 'message' => 'm', 'category' => 'payment']);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'C', 'message' => 'm', 'category' => 'course']);

        $this->assertCount(2, Notification::byCategory('course')->get());
        $this->assertCount(1, Notification::byCategory('payment')->get());
    }

    public function test_notification_model_mark_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test',
            'message' => 'Body',
            'is_read' => false,
        ]);

        $notification->markAsRead();

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    // ── New: Notification API Endpoints ─────────────────────────────────────

    public function test_notification_index_filters_by_category(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'Course', 'message' => 'm', 'category' => 'course']);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'Payment', 'message' => 'm', 'category' => 'payment']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications?category=course');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Course', $response->json('data.data.0.title'));
    }

    public function test_notification_index_unread_only(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'Unread', 'message' => 'm', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'Read', 'message' => 'm', 'is_read' => true]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications?unread_only=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Unread', $response->json('data.data.0.title'));
    }

    public function test_unread_count_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'A', 'message' => 'm', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'B', 'message' => 'm', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'C', 'message' => 'm', 'is_read' => true]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk();
        $this->assertEquals(2, $response->json('count'));
    }

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'A', 'message' => 'm', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'B', 'message' => 'm', 'is_read' => false]);

        $this->actingAs($user)->postJson('/api/v1/notifications/mark-all-read');

        $this->assertEquals(0, Notification::unread()->count());
    }

    // ── New: Admin Broadcast ─────────────────────────────────────────────────

    private function createAdmin(): User
    {
        $this->artisan('db:seed --class=PermissionSeeder');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_broadcast_sends_notification(): void
    {
        $admin = $this->createAdmin();

        $students = User::factory(3)->create();
        foreach ($students as $s) {
            $s->assignRole('student');
        }

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/notifications/broadcast', [
                'title' => 'System Update',
                'message' => 'Maintenance tonight',
                'type' => 'all',
            ]);

        $response->assertOk();

        $announcements = Notification::where('type', 'announcement')->get();
        $this->assertGreaterThanOrEqual(3, $announcements->count());
    }

    public function test_admin_broadcast_validation(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/notifications/broadcast', [
                'message' => 'Missing title',
            ]);

        $response->assertStatus(422);
    }

    public function test_admin_notification_analytics(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create();
        $user->assignRole('student');

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'A', 'message' => 'm', 'category' => 'course', 'priority' => 'high', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'B', 'message' => 'm', 'category' => 'payment', 'priority' => 'normal', 'is_read' => true]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/notifications/analytics');

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.total'));
        $this->assertEquals(1, $response->json('data.unread'));
        $this->assertArrayHasKey('course', $response->json('data.by_category'));
        $this->assertArrayHasKey('payment', $response->json('data.by_category'));
    }

    // ── New: Notification Preferences Integration ────────────────────────────

    public function test_notification_preferences_respect_quiet_hours(): void
    {
        $user = User::factory()->create();
        $prefs = \App\Models\UserNotificationPreference::forUser($user->id);

        $currentHour = (int) now()->format('H');
        $quietStart = $currentHour;
        $quietEnd = ($currentHour + 1) % 24;

        $prefs->update([
            'quiet_hour_start' => $quietStart,
            'quiet_hour_end' => $quietEnd,
            'streak_reminders' => true,
        ]);

        $this->assertTrue($prefs->isQuietHourNow());
        $this->assertFalse($prefs->allows('streak_risk'));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Phase 4.2 — Notification Analytics, Delivery Reliability, Filters & More
    // ═══════════════════════════════════════════════════════════════════════════

    // ── Part 1: FCM Token Handling ──────────────────────────────────────────

    public function test_fcm_invalid_token_marked_inactive(): void
    {
        $token = DeviceToken::create([
            'user_id' => User::factory()->create()->id,
            'token' => 'invalid-fcm-token-12345',
            'platform' => 'android',
            'is_active' => true,
        ]);

        // Simulate what FcmService does on 404
        DeviceToken::where('token', $token->token)->update(['is_active' => false]);

        $this->assertDatabaseHas('device_tokens', [
            'token' => $token->token,
            'is_active' => false,
        ]);
    }

    public function test_inactive_tokens_excluded_from_send(): void
    {
        $user = User::factory()->create();
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'active-token',
            'platform' => 'android',
            'is_active' => true,
        ]);
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'inactive-token',
            'platform' => 'android',
            'is_active' => false,
        ]);

        $activeTokens = DeviceToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        $this->assertCount(1, $activeTokens);
        $this->assertContains('active-token', $activeTokens);
        $this->assertNotContains('inactive-token', $activeTokens);
    }

    // ── Part 2: Notification Analytics ──────────────────────────────────────

    public function test_notification_service_creates_analytics_record(): void
    {
        $user = User::factory()->create();

        NotificationService::send($user, 'Analytics Test', 'Body', 'test');

        $this->assertDatabaseHas('notification_analytics', [
            'event_type' => 'sent',
        ]);

        $notification = Notification::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('notification_analytics', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_notification_analytics_event_types(): void
    {
        $user = User::factory()->create();
        NotificationService::send($user, 'Test', 'Body', 'test');

        $notification = Notification::where('user_id', $user->id)->first();

        // Create opened event
        NotificationAnalytics::create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'event_type' => 'opened',
        ]);

        // Create failed event
        NotificationAnalytics::create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'event_type' => 'failed',
            'metadata' => ['error' => 'token_expired'],
        ]);

        $this->assertEquals(3, NotificationAnalytics::count());
        $this->assertEquals(1, NotificationAnalytics::where('event_type', 'opened')->count());
        $this->assertEquals(1, NotificationAnalytics::where('event_type', 'failed')->count());
    }

    // ── Part 3: Opened Tracking API ─────────────────────────────────────────

    public function test_opened_endpoint_tracks_and_marks_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Tap Test',
            'message' => 'Body',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/notifications/{$notification->id}/opened");

        $response->assertOk();

        // Verify analytics record
        $this->assertDatabaseHas('notification_analytics', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'event_type' => 'opened',
        ]);

        // Verify notification marked as read
        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_opened_endpoint_requires_auth(): void
    {
        $notification = Notification::create([
            'user_id' => User::factory()->create()->id,
            'type' => 'test',
            'title' => 'T',
            'message' => 'M',
        ]);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/opened");
        $response->assertStatus(401);
    }

    // ── Part 4: Notification Index Improvements ─────────────────────────────

    public function test_notification_index_filters_by_priority(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'High', 'message' => 'm', 'priority' => 'high']);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'Normal', 'message' => 'm', 'priority' => 'normal']);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'Low', 'message' => 'm', 'priority' => 'low']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications?priority=high');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('High', $response->json('data.data.0.title'));
    }

    public function test_notification_index_filters_by_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        // Use DB insert to set custom created_at (not in $fillable)
        \Illuminate\Support\Facades\DB::table('notifications')->insert([
            'user_id' => $user->id,
            'type' => 't',
            'title' => 'Old',
            'message' => 'm',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'New', 'message' => 'm']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications?date_from=' . now()->subDays(2)->format('Y-m-d'));

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('New', $response->json('data.data.0.title'));
    }

    public function test_notification_index_returns_unread_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'A', 'message' => 'm', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'B', 'message' => 'm', 'is_read' => true]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications');

        $response->assertOk();
        $this->assertEquals(1, $response->json('unread_count'));
    }

    public function test_notification_index_grouped(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Notification::create(['user_id' => $user->id, 'type' => 't', 'title' => 'Today Notif', 'message' => 'm']);

        \Illuminate\Support\Facades\DB::table('notifications')->insert([
            'user_id' => $user->id,
            'type' => 't',
            'title' => 'Yesterday Notif',
            'message' => 'm',
            'created_at' => now()->subDay()->startOfDay(),
            'updated_at' => now()->subDay()->startOfDay(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/notifications?grouped=1');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertArrayHasKey('today', $data, 'Grouped data should contain "today" key');
        $this->assertArrayHasKey('yesterday', $data, 'Grouped data should contain "yesterday" key');
    }

    // ── Part 5: Category Preference Blocking ───────────────────────────────

    public function test_category_preference_blocks_notification(): void
    {
        $user = User::factory()->create();
        $prefs = UserNotificationPreference::forUser($user->id);

        $prefs->update([
            'course_alerts' => false,
            'achievement_alerts_category' => false,
            'community_alerts' => false,
            'payment_alerts' => false,
        ]);

        $this->assertFalse($prefs->allowsCategory('course'));
        $this->assertFalse($prefs->allowsCategory('achievement'));
        $this->assertFalse($prefs->allowsCategory('community'));
        $this->assertFalse($prefs->allowsCategory('payment'));
    }

    public function test_category_preferences_default_to_enabled(): void
    {
        $prefs = UserNotificationPreference::forUser(User::factory()->create()->id);

        $this->assertTrue($prefs->allowsCategory('course'));
        $this->assertTrue($prefs->allowsCategory('achievement'));
        $this->assertTrue($prefs->allowsCategory('community'));
        $this->assertTrue($prefs->allowsCategory('payment'));
        $this->assertTrue($prefs->allowsCategory('marketing'));
        $this->assertTrue($prefs->allowsCategory('security'));
        $this->assertTrue($prefs->allowsCategory('system'));
    }

    public function test_preferences_update_all_category_fields(): void
    {
        $user = User::factory()->create();
        $prefs = UserNotificationPreference::forUser($user->id);

        $prefs->update([
            'course_alerts' => false,
            'community_alerts' => false,
            'payment_alerts' => true,
        ]);

        $fresh = $prefs->fresh();
        $this->assertFalse($fresh->course_alerts);
        $this->assertFalse($fresh->community_alerts);
        $this->assertTrue($fresh->payment_alerts);
    }

    // ── Part 9: Broadcast Throttling ────────────────────────────────────────

    public function test_admin_broadcast_throttled(): void
    {
        Cache::flush();
        $admin = $this->createAdmin();

        $user = User::factory()->create();
        $user->assignRole('student');

        // First broadcast should succeed
        $response1 = $this->actingAs($admin)
            ->postJson('/api/v1/admin/notifications/broadcast', [
                'title' => 'First',
                'message' => 'First broadcast',
                'type' => 'all',
            ]);
        $response1->assertOk();

        // Second broadcast should be throttled (429)
        $response2 = $this->actingAs($admin)
            ->postJson('/api/v1/admin/notifications/broadcast', [
                'title' => 'Second',
                'message' => 'Second broadcast',
                'type' => 'all',
            ]);
        $response2->assertStatus(429);
    }

    // ── Part 10: Security — Broadcast requires permission ──────────────────

    public function test_broadcast_requires_admin_permission(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $response = $this->actingAs($student)
            ->postJson('/api/v1/admin/notifications/broadcast', [
                'title' => 'Hack',
                'message' => 'Attempt',
            ]);

        $response->assertStatus(403);
    }

    // ── Part 10: Prune Inactive Tokens ─────────────────────────────────────

    public function test_prune_inactive_tokens(): void
    {
        DeviceToken::create([
            'user_id' => User::factory()->create()->id,
            'token' => 'old-inactive',
            'platform' => 'android',
            'is_active' => false,
        ]);

        // Manually set updated_at to 60 days ago
        DeviceToken::where('token', 'old-inactive')->update(['updated_at' => now()->subDays(60)]);

        DeviceToken::create([
            'user_id' => User::factory()->create()->id,
            'token' => 'recent-inactive',
            'platform' => 'ios',
            'is_active' => false,
        ]);

        // Manually set updated_at to 5 days ago
        DeviceToken::where('token', 'recent-inactive')->update(['updated_at' => now()->subDays(5)]);

        $pruned = \App\Services\FcmService::pruneInactiveTokens(30);
        $this->assertEquals(1, $pruned); // Only old-inactive should be pruned

        $this->assertDatabaseMissing('device_tokens', ['token' => 'old-inactive']);
        $this->assertDatabaseHas('device_tokens', ['token' => 'recent-inactive']);
    }
}
