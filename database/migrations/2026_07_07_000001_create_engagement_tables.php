<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Teacher/Course Stories ────────────────────────────────────────
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('media_type')->default('image'); // image, video
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'expires_at']);
            $table->index(['user_id', 'course_id']);
        });

        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();
            $table->unique(['story_id', 'user_id']);
            $table->index('story_id');
            $table->index('user_id');
        });

        // ── Referral Settings ────────────────────────────────────────────
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('reward_amount', 10, 2)->default(5.00);
            $table->string('reward_type')->default('wallet'); // wallet, xp, both
            $table->integer('xp_reward')->default(0);
            $table->integer('max_rewards_per_user')->nullable();
            $table->integer('max_rewards_total')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default settings
        DB::table('referral_settings')->insert([
            'reward_amount' => 5.00,
            'reward_type' => 'wallet',
            'xp_reward' => 50,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('story_views');
        Schema::dropIfExists('stories');
        Schema::dropIfExists('referral_settings');
    }
};
