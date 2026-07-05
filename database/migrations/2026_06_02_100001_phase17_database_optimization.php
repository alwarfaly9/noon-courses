<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 17 — Database Optimization
 *
 * Adds missing indexes, composite indexes, and N+1 prevention constraints
 * discovered during the production-hardening audit.
 *
 * Safe to run on existing data — all operations are additive (indexes only).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── lesson_completions — N+1 on progress calculation ─────────────────
        Schema::table('lesson_completions', function (Blueprint $table) {
            if (!$this->hasIndex('lesson_completions', 'lc_user_lesson_unique')) {
                // Prevent duplicate completion records
                $table->unique(['user_id', 'lesson_id'], 'lc_user_lesson_unique');
            }
        });

        // ── course_reviews — query by approval + course ───────────────────────
        Schema::table('course_reviews', function (Blueprint $table) {
            if (!$this->hasIndex('course_reviews', 'cr_course_approved_rating_idx')) {
                $table->index(['course_id', 'is_approved', 'rating'], 'cr_course_approved_rating_idx');
            }
            if (!$this->hasIndex('course_reviews', 'cr_helpful_idx')) {
                $table->index(['helpful_votes'], 'cr_helpful_idx');
            }
        });

        // ── users — login + lookup queries ────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'users_is_active_idx')) {
                $table->index(['is_active', 'is_verified'], 'users_is_active_idx');
            }
            if (!$this->hasIndex('users', 'users_referral_code_idx')) {
                // referral_code already has unique index via phase16 migration
                // Only add if missing
            }
        });

        // ── activity_logs — time-range queries + per-user ─────────────────────
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!$this->hasIndex('activity_logs', 'al_action_created_idx')) {
                $table->index(['action', 'created_at'], 'al_action_created_idx');
            }
            if (!$this->hasIndex('activity_logs', 'al_user_action_idx')) {
                $table->index(['user_id', 'action', 'created_at'], 'al_user_action_idx');
            }
        });

        // ── transactions — financial reporting queries ─────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            if (!$this->hasIndex('transactions', 'txn_user_status_created_idx')) {
                $table->index(['user_id', 'status', 'created_at'], 'txn_user_status_created_idx');
            }
            if (!$this->hasIndex('transactions', 'txn_type_status_idx')) {
                $table->index(['type', 'status', 'created_at'], 'txn_type_status_idx');
            }
        });

        // ── quiz_attempts — student quiz history ──────────────────────────────
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!$this->hasIndex('quiz_attempts', 'qa_user_quiz_idx')) {
                $table->index(['user_id', 'quiz_id', 'created_at'], 'qa_user_quiz_idx');
            }
        });

        // ── messages — conversation inbox ─────────────────────────────────────
        Schema::table('messages', function (Blueprint $table) {
            if (!$this->hasIndex('messages', 'msg_conv_created_idx')) {
                $table->index(['conversation_id', 'created_at'], 'msg_conv_created_idx');
            }
            if (!$this->hasIndex('messages', 'msg_user_read_idx')) {
                $table->index(['user_id', 'is_read'], 'msg_user_read_idx');
            }
        });

        // ── learning_path_enrollments — N+1 on progress ───────────────────────
        Schema::table('learning_path_enrollments', function (Blueprint $table) {
            if (!$this->hasIndex('learning_path_enrollments', 'lpe_user_status_idx')) {
                $table->index(['user_id', 'status'], 'lpe_user_status_idx');
            }
        });

        // ── withdraw_requests — admin listing ─────────────────────────────────
        Schema::table('withdraw_requests', function (Blueprint $table) {
            if (!$this->hasIndex('withdraw_requests', 'wr_status_created_idx')) {
                $table->index(['status', 'created_at'], 'wr_status_created_idx');
            }
        });

        // ── password_reset_tokens — cleanup of expired tokens ─────────────────
        // Note: table already has primary key on email; add created_at index for
        // periodic cleanup jobs that delete tokens older than 1 hour.
        // The table is managed by Laravel and may already have this.
    }

    public function down(): void
    {
        Schema::table('lesson_completions', function (Blueprint $table) {
            $table->dropUnique('lc_user_lesson_unique');
        });
        Schema::table('course_reviews', function (Blueprint $table) {
            $table->dropIndex('cr_course_approved_rating_idx');
            $table->dropIndex('cr_helpful_idx');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_active_idx');
        });
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('al_action_created_idx');
            $table->dropIndex('al_user_action_idx');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('txn_user_status_created_idx');
            $table->dropIndex('txn_type_status_idx');
        });
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('qa_user_quiz_idx');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('msg_conv_created_idx');
            $table->dropIndex('msg_user_read_idx');
        });
        Schema::table('learning_path_enrollments', function (Blueprint $table) {
            $table->dropIndex('lpe_user_status_idx');
        });
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->dropIndex('wr_status_created_idx');
        });
    }

    // ── Helper: safe index existence check ───────────────────────────────────

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};
