<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private Course $paidCourse;
    private Course $freeCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->assignRole('student');
        Credit::create(['user_id' => $this->student->id, 'balance' => 200]);

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole('teacher');

        $this->paidCourse = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'price'      => 50,
            'status'     => 'published',
        ]);

        $this->freeCourse = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'price'      => 0,
            'status'     => 'published',
        ]);
    }

    public function test_student_can_enroll_in_paid_course(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->paidCourse->id}/enroll");

        $response->assertOk();
        $this->assertDatabaseHas('course_enrollments', [
            'student_id' => $this->student->id,
            'course_id'  => $this->paidCourse->id,
        ]);
        $this->assertEquals(150, $this->student->credits->fresh()->balance);
    }

    public function test_student_can_enroll_in_free_course(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->freeCourse->id}/enroll");

        $response->assertOk();
        $this->assertDatabaseHas('course_enrollments', [
            'student_id' => $this->student->id,
            'course_id'  => $this->freeCourse->id,
        ]);
        $this->assertEquals(200, $this->student->credits->fresh()->balance);
    }

    public function test_student_cannot_enroll_with_insufficient_balance(): void
    {
        $this->student->credits->update(['balance' => 10]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->paidCourse->id}/enroll");

        $response->assertStatus(400);
        $this->assertDatabaseMissing('course_enrollments', [
            'student_id' => $this->student->id,
            'course_id'  => $this->paidCourse->id,
        ]);
    }

    public function test_student_cannot_enroll_twice(): void
    {
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->paidCourse->id}/enroll");

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$this->paidCourse->id}/enroll");

        $response->assertStatus(400);
    }

    public function test_student_cannot_enroll_in_unpublished_course(): void
    {
        $draftCourse = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'price'      => 0,
            'status'     => 'draft',
        ]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$draftCourse->id}/enroll");

        $response->assertStatus(400);
    }
}
