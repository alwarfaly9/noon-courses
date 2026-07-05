<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeEnrolledStudent(Course $course): User
    {
        $student = User::factory()->create(['role' => 'student']);
        CourseEnrollment::factory()->create([
            'student_id' => $student->id,
            'course_id'  => $course->id,
        ]);
        return $student;
    }

    public function test_enrolled_student_can_submit_review(): void
    {
        $course  = Course::factory()->create(['status' => 'published']);
        $student = $this->makeEnrolledStudent($course);

        $response = $this->actingAs($student)
            ->postJson("/api/v1/courses/{$course->id}/reviews", [
                'rating' => 5,
                'review' => 'Excellent course!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('course_reviews', ['course_id' => $course->id, 'user_id' => $student->id]);
    }

    public function test_unenrolled_student_cannot_submit_review(): void
    {
        $course  = Course::factory()->create(['status' => 'published']);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)
            ->postJson("/api/v1/courses/{$course->id}/reviews", [
                'rating' => 4,
                'review' => 'Good.',
            ]);

        $response->assertStatus(403);
    }

    public function test_student_cannot_edit_review_older_than_30_days(): void
    {
        $course  = Course::factory()->create(['status' => 'published']);
        $student = $this->makeEnrolledStudent($course);
        $review  = CourseReview::factory()->create([
            'course_id'  => $course->id,
            'user_id'    => $student->id,
            'created_at' => now()->subDays(31),
        ]);

        $response = $this->actingAs($student)
            ->putJson("/api/v1/reviews/{$review->id}", ['rating' => 3]);

        $response->assertStatus(403);
    }

    public function test_student_cannot_edit_another_students_review(): void
    {
        $course   = Course::factory()->create(['status' => 'published']);
        $owner    = $this->makeEnrolledStudent($course);
        $attacker = $this->makeEnrolledStudent($course);
        $review   = CourseReview::factory()->create([
            'course_id' => $course->id,
            'user_id'   => $owner->id,
        ]);

        $response = $this->actingAs($attacker)
            ->putJson("/api/v1/reviews/{$review->id}", ['rating' => 1]);

        $response->assertStatus(403);
    }

    public function test_student_can_mark_review_helpful(): void
    {
        $course  = Course::factory()->create(['status' => 'published']);
        $student = $this->makeEnrolledStudent($course);
        $other   = $this->makeEnrolledStudent($course);
        $review  = CourseReview::factory()->create([
            'course_id'    => $course->id,
            'user_id'      => $other->id,
            'helpful_votes' => 0,
        ]);

        $response = $this->actingAs($student)
            ->postJson("/api/v1/reviews/{$review->id}/helpful");

        $response->assertStatus(200)->assertJsonPath('success', true);
    }
}
