<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedInteger('xp_this_week')->default(0);
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedSmallInteger('current_streak_days')->default(0);
            $table->unsignedSmallInteger('longest_streak_days')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->unsignedInteger('lessons_completed')->default(0);
            $table->unsignedInteger('courses_completed')->default(0);
            $table->unsignedInteger('quizzes_passed')->default(0);
            $table->unsignedInteger('paths_completed')->default(0);
            $table->timestamps();

            $table->index('xp_total');
            $table->index('level');
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable()->comment('SVG name or URL');
            $table->enum('type', ['lesson', 'course', 'streak', 'quiz', 'path', 'level', 'special'])->default('special');
            $table->string('condition_type')->nullable()->comment('e.g. lessons_completed, streak_days');
            $table->unsignedInteger('condition_value')->default(1);
            $table->unsignedInteger('xp_reward')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('badge_id')->constrained()->onDelete('cascade');
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'badge_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('user_stats');
    }
};
