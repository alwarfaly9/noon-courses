<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('total_users')->default(0);
            $table->integer('new_users')->default(0);
            $table->integer('active_users')->default(0);
            $table->integer('enrollments')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->integer('quiz_attempts')->default(0);
            $table->integer('quizzes_passed')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->integer('achievements_unlocked')->default(0);
            $table->integer('stories_created')->default(0);
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
    }
};
