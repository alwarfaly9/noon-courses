<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily learning goals per user
        Schema::create('user_daily_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('xp_target')->default(50);
            $table->unsignedTinyInteger('lessons_target')->default(2);
            $table->boolean('streak_active')->default(false);
            $table->date('goal_date');
            $table->unsignedSmallInteger('xp_earned_today')->default(0);
            $table->unsignedTinyInteger('lessons_done_today')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'goal_date']);
            $table->index('goal_date');
        });

        // Notification preferences per user
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();

            // Channel toggles
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);

            // Behavioral triggers
            $table->boolean('streak_reminders')->default(true);
            $table->boolean('inactivity_reminders')->default(true);
            $table->boolean('achievement_alerts')->default(true);
            $table->boolean('quiz_retry_reminders')->default(true);
            $table->boolean('path_reminders')->default(true);
            $table->boolean('community_replies')->default(true);
            $table->boolean('teacher_announcements')->default(true);
            $table->boolean('recommended_content')->default(true);

            // Quiet hours (stored as hour 0–23, UTC)
            $table->unsignedTinyInteger('quiet_hour_start')->default(23);
            $table->unsignedTinyInteger('quiet_hour_end')->default(7);

            $table->timestamps();
        });

        // Track which behavioral notifications have been sent so we don't spam
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('trigger_type', 64); // inactivity, streak_risk, quiz_retry …
            $table->string('reference_id')->nullable(); // course_id, quiz_id, path_id
            $table->timestamp('sent_at');

            $table->index(['user_id', 'trigger_type', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('user_notification_preferences');
        Schema::dropIfExists('user_daily_goals');
    }
};
