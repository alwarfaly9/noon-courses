<?php

namespace Tests\Feature\Permissions;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->assignRole('student');

        $this->course = Course::factory()->create([
            'status' => 'published',
        ]);
    }

    public function test_student_can_view_courses(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/courses');

        $response->assertOk();
    }

    public function test_student_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_student_cannot_access_teacher_routes(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/teacher/courses');

        $response->assertStatus(403);
    }

    public function test_student_can_enroll_in_free_course(): void
    {
        $freeCourse = Course::factory()->create([
            'status' => 'published',
            'price'  => 0,
        ]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/student/courses/{$freeCourse->id}/enroll");

        $response->assertOk();
        $this->assertDatabaseHas('course_enrollments', [
            'student_id' => $this->student->id,
            'course_id'  => $freeCourse->id,
        ]);
    }

    public function test_student_can_view_own_profile(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/auth/user');

        $response->assertOk();
    }

    public function test_student_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/student/dashboard');

        $response->assertOk();
    }

    public function test_unauthenticated_user_is_blocked(): void
    {
        $response = $this->getJson('/api/v1/auth/user');
        $response->assertStatus(401);
    }
}
