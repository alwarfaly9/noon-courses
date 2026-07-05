<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->assignRole('student');

        Credit::create(['user_id' => $this->student->id, 'balance' => 100]);

        $this->course = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'price' => 50,
            'status' => 'published',
        ]);
    }

    public function test_student_can_enroll_in_paid_course(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->course->id}/enroll");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('course_enrollments', [
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        // Check balance was deducted
        $this->assertEquals(50, $this->student->credits->fresh()->balance);
    }

    public function test_student_cannot_enroll_with_insufficient_balance(): void
    {
        $this->student->credits->update(['balance' => 10]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->course->id}/enroll");

        $response->assertStatus(400);

        $this->assertDatabaseMissing('course_enrollments', [
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_student_cannot_enroll_twice(): void
    {
        // First enrollment
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->course->id}/enroll");

        // Second enrollment attempt
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->course->id}/enroll");

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_student_can_enroll_in_free_course(): void
    {
        $freeCourse = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'price' => 0,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$freeCourse->id}/enroll");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
