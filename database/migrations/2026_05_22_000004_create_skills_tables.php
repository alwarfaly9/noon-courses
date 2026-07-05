<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('users_count')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });

        Schema::create('course_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->timestamps();

            $table->unique(['course_id', 'skill_id'], 'unique_course_skill');
            $table->index('skill_id');
        });

        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->foreignId('earned_via_course_id')->nullable()->constrained('courses')->onDelete('set null');
            $table->foreignId('earned_via_path_id')->nullable()->constrained('learning_paths')->onDelete('set null');
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'skill_id'], 'unique_user_skill');
            $table->index('user_id');
            $table->index('skill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('course_skills');
        Schema::dropIfExists('skills');
    }
};
