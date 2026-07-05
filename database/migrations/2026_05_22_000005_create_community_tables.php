<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('course_lessons')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('lesson_comments')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->unsignedSmallInteger('reported_count')->default(0);
            $table->unsignedSmallInteger('replies_count')->default(0);
            $table->unsignedSmallInteger('likes_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('lesson_id');
            $table->index('user_id');
            $table->index('parent_id');
            $table->index(['lesson_id', 'parent_id']); // Fetch top-level per lesson fast
        });

        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('lesson_comments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['like'])->default('like');
            $table->timestamps();

            $table->unique(['comment_id', 'user_id'], 'unique_comment_reaction');
            $table->index('comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
        Schema::dropIfExists('lesson_comments');
    }
};
