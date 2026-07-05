<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Onboarding state on users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('onboarding_completed')->default(false)->after('is_active');
        });

        // What categories/topics a user is interested in
        Schema::create('user_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'category_id']);
        });

        // Self-declared skill goals (what the user wants to learn)
        Schema::create('user_skill_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'skill_id']);
        });

        // Track which milestone XP rewards have already been granted for a path enrollment
        // Prevents duplicate awards on progress recalculations
        Schema::table('learning_path_enrollments', function (Blueprint $table) {
            $table->unsignedTinyInteger('last_milestone_rewarded')->default(0)->after('progress_percentage');
        });

        // Community engagement tracking (engagement score per user)
        Schema::table('user_stats', function (Blueprint $table) {
            $table->unsignedInteger('comments_posted')->default(0)->after('paths_completed');
            $table->unsignedInteger('helpful_votes_received')->default(0)->after('comments_posted');
            $table->unsignedInteger('engagement_score')->default(0)->after('helpful_votes_received');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn($t) => $t->dropColumn('onboarding_completed'));
        Schema::dropIfExists('user_skill_goals');
        Schema::dropIfExists('user_interests');
        Schema::table('learning_path_enrollments', fn($t) => $t->dropColumn('last_milestone_rewarded'));
        Schema::table('user_stats', function ($t) {
            $t->dropColumn(['comments_posted', 'helpful_votes_received', 'engagement_score']);
        });
    }
};
