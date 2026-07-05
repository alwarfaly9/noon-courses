<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Referrals ──────────────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 12)->nullable()->unique()->after('onboarding_completed');
            }
            if (!Schema::hasColumn('users', 'is_verified_instructor')) {
                $table->boolean('is_verified_instructor')->default(false)->after('referral_code');
            }
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_id')->nullable()->constrained('users')->nullOnDelete();
            // Status: pending (registered) | converted (enrolled in first course) | rewarded
            $table->enum('status', ['pending', 'converted', 'rewarded'])->default('pending');
            $table->decimal('reward_amount', 10, 2)->default(0);
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index('referrer_id');
            $table->index(['referred_id', 'status']);
        });

        // ── Review enhancements ────────────────────────────────────────────────
        Schema::table('course_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('course_reviews', 'helpful_votes')) {
                $table->unsignedInteger('helpful_votes')->default(0)->after('review');
            }
            if (!Schema::hasColumn('course_reviews', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('helpful_votes');
            }
            if (!Schema::hasColumn('course_reviews', 'is_approved')) {
                $table->boolean('is_approved')->default(true)->after('is_featured');
            }
        });

        Schema::create('review_helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('course_reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['review_id', 'user_id']);
        });

        // ── Growth campaigns ───────────────────────────────────────────────────
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type'); // weekly_challenge | monthly | seasonal
            $table->text('description')->nullable();
            $table->string('banner_image_url')->nullable();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->foreignId('reward_badge_id')->nullable()->constrained('badges')->nullOnDelete();
            // Goal: lesson_count | xp_amount | streak_days | course_complete
            $table->string('goal_type')->nullable();
            $table->unsignedInteger('goal_value')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('campaign_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index(['user_id', 'completed']);
        });

        // ── Promotional banners ────────────────────────────────────────────────
        Schema::create('promotional_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_url')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->string('background_color', 10)->default('#57247A');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // ── Success stories ────────────────────────────────────────────────────
        Schema::create('success_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('before_description')->nullable();
            $table->string('after_description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index(['is_approved', 'is_featured']);
        });

        // ── Indexes for performance ────────────────────────────────────────────
        Schema::table('certificates', function (Blueprint $table) {
            // add index only if it doesn't already exist — use SHOW INDEX to avoid Doctrine dependency
            $connection = Schema::getConnection();
            try {
                $indexes = $connection->select("SHOW INDEX FROM certificates WHERE Column_name = 'certificate_id'");
            } catch (\Exception $e) {
                $indexes = [];
            }
            if (empty($indexes)) {
                $table->index('certificate_id');
            }
        });

        Schema::table('course_reviews', function (Blueprint $table) {
            $table->index(['course_id', 'is_approved']);
            $table->index(['helpful_votes']);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn($t) => $t->dropColumn(['referral_code', 'is_verified_instructor']));
        Schema::dropIfExists('referrals');
        Schema::table('course_reviews', fn($t) => $t->dropColumn(['helpful_votes', 'is_featured', 'is_approved']));
        Schema::dropIfExists('review_helpful_votes');
        Schema::dropIfExists('campaign_participations');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('promotional_banners');
        Schema::dropIfExists('success_stories');
        Schema::table('certificates', fn($t) => $t->dropIndex(['certificate_id']));
        Schema::table('course_reviews', function ($t) {
            $t->dropIndex(['course_id', 'is_approved']);
            $t->dropIndex(['helpful_votes']);
        });
    }
};