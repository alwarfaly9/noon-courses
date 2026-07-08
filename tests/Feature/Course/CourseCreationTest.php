<?php

namespace Tests\Feature\Course;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=PermissionSeeder');

        $this->category = Category::factory()->create();

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole('teacher');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_teacher_can_create_course(): void
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/v1/teacher/courses', [
                'title'       => 'New Course',
                'description' => 'Course description',
                'category_id' => $this->category->id,
                'price'       => 100,
                'level'       => 'beginner',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('courses', ['title' => 'New Course']);
    }

    public function test_new_course_starts_as_pending(): void
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/v1/teacher/courses', [
                'title'       => 'Draft Course',
                'description' => 'Description',
                'category_id' => $this->category->id,
                'price'       => 0,
                'level'       => 'beginner',
            ]);

        $courseId = $response->json('data.id');
        $this->assertNotNull($courseId, 'Course creation should return a valid ID');
        $course = Course::find($courseId);
        $this->assertNotNull($course, 'Course should exist in database');
        $this->assertEquals('pending', $course->status);
    }

    public function test_teacher_can_update_own_course(): void
    {
        $course = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'status'     => 'draft',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/v1/teacher/courses/{$course->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Updated Title']);
    }

    public function test_teacher_cannot_update_other_teachers_course(): void
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $course = Course::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/v1/teacher/courses/{$course->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_approve_course(): void
    {
        $course = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/courses/{$course->id}/approve");

        $response->assertOk();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'status' => 'published']);
    }

    public function test_admin_can_reject_course(): void
    {
        $course = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/courses/{$course->id}/reject", [
                'reason' => 'Incomplete content',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'status' => 'rejected']);
    }
}
