<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes, unique constraints, and foreign keys for performance and data integrity.
     */
    public function up(): void
    {
        // Add indexes for frequently queried foreign keys
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('course_id');
            $table->index('status');
            $table->index('type');
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->index('course_id');
            $table->index('student_id');
            // Unique constraint: a student can only enroll once per course
            $table->unique(['student_id', 'course_id'], 'unique_student_course_enrollment');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index('teacher_id');
            $table->index('category_id');
            $table->index('status');
        });

        Schema::table('course_sections', function (Blueprint $table) {
            $table->index('course_id');
        });

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->index('course_id');
            $table->index('section_id');
        });

        Schema::table('course_reviews', function (Blueprint $table) {
            $table->index('course_id');
            $table->index('user_id');
            $table->unique(['course_id', 'user_id'], 'unique_course_user_review');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('is_read');
        });

        Schema::table('credit_cards', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['course_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['student_id']);
            $table->dropUnique('unique_student_course_enrollment');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['teacher_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
        });

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['section_id']);
        });

        Schema::table('course_reviews', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['user_id']);
            $table->dropUnique('unique_course_user_review');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_read']);
        });

        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
