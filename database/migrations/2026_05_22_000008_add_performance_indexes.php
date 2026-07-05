<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for tables that will handle 100k+ users.
 * Run AFTER all feature migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // course_enrollments — hot path for progress queries
        Schema::table('course_enrollments', function (Blueprint $table) {
            if (!$this->hasIndex('course_enrollments', 'ce_student_status_idx')) {
                $table->index(['student_id', 'status'], 'ce_student_status_idx');
            }
            if (!$this->hasIndex('course_enrollments', 'ce_course_completed_idx')) {
                $table->index(['course_id', 'completed_at'], 'ce_course_completed_idx');
            }
        });

        // courses — listing + search
        Schema::table('courses', function (Blueprint $table) {
            if (!$this->hasIndex('courses', 'courses_status_featured_idx')) {
                $table->index(['status', 'is_featured'], 'courses_status_featured_idx');
            }
            if (!$this->hasIndex('courses', 'courses_category_status_idx')) {
                $table->index(['category_id', 'status'], 'courses_category_status_idx');
            }
            if (!$this->hasIndex('courses', 'courses_teacher_status_idx')) {
                $table->index(['teacher_id', 'status'], 'courses_teacher_status_idx');
            }
        });

        // lesson_completions — progress recalculation
        Schema::table('lesson_completions', function (Blueprint $table) {
            if (!$this->hasIndex('lesson_completions', 'lc_user_course_idx')) {
                $table->index(['user_id', 'course_id'], 'lc_user_course_idx');
            }
        });

        // notifications — inbox queries
        Schema::table('notifications', function (Blueprint $table) {
            if (!$this->hasIndex('notifications', 'notif_user_read_idx')) {
                $table->index(['user_id', 'is_read', 'created_at'], 'notif_user_read_idx');
            }
        });

        // user_stats — leaderboard sort
        Schema::table('user_stats', function (Blueprint $table) {
            if (!$this->hasIndex('user_stats', 'us_xp_total_idx')) {
                $table->index('xp_total', 'us_xp_total_idx');
            }
        });

        // user_skills — profile lookups
        Schema::table('user_skills', function (Blueprint $table) {
            if (!$this->hasIndex('user_skills', 'usk_user_level_idx')) {
                $table->index(['user_id', 'level'], 'usk_user_level_idx');
            }
        });
    }

    public function down(): void
    {
        // Indexes are additive — no harm leaving them, but clean up for symmetry
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropIndex('ce_student_status_idx');
            $table->dropIndex('ce_course_completed_idx');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_status_featured_idx');
            $table->dropIndex('courses_category_status_idx');
            $table->dropIndex('courses_teacher_status_idx');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return \Illuminate\Support\Facades\Schema::hasIndex($table, $indexName);
    }
};
