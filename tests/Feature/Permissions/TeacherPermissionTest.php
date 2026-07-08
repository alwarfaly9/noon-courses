<?php

namespace Tests\Feature\Permissions;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private User $student;
    private Course $ownCourse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=PermissionSeeder');

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole('teacher');

        $this->otherTeacher = User::factory()->create(['is_active' => true]);
        $this->otherTeacher->assignRole('teacher');

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->assignRole('student');

        $this->ownCourse = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'status'     => 'draft',
        ]);
    }

    public function test_teacher_can_manage_own_courses(): void
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/v1/teacher/courses');

        $response->assertOk();
    }

    public function test_teacher_cannot_modify_another_teachers_course(): void
    {
        $otherCourse = Course::factory()->create([
            'teacher_id' => $this->otherTeacher->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/v1/teacher/courses/{$otherCourse->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    public function test_teacher_can_create_course_content(): void
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/v1/teacher/courses/{$this->ownCourse->id}/sections", [
                'title' => 'New Section',
            ]);

        $response->assertStatus(201);
    }

    public function test_teacher_cannot_create_content_in_another_course(): void
    {
        $otherCourse = Course::factory()->create([
            'teacher_id' => $this->otherTeacher->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/v1/teacher/courses/{$otherCourse->id}/sections", [
                'title' => 'New Section',
            ]);

        $response->assertStatus(403);
    }

    public function test_student_cannot_access_teacher_routes(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/teacher/courses');

        $response->assertStatus(403);
    }

    public function test_teacher_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/v1/teacher/dashboard');

        $response->assertOk();
    }
}
