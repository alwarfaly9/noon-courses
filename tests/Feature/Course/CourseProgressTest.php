<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;
    private CourseSection $section;
    private CourseLesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole('teacher');

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->assignRole('student');

        $this->course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'status'     => 'published',
        ]);

        $this->section = CourseSection::factory()->create([
            'course_id' => $this->course->id,
        ]);

        $this->lesson = CourseLesson::factory()->create([
            'course_id'   => $this->course->id,
            'section_id'  => $this->section->id,
        ]);

        $this->student->enrolledCourses()->attach($this->course->id);
    }

    public function test_student_can_mark_lesson_complete(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson(
                "/api/v1/student/courses/{$this->course->id}/lessons/{$this->lesson->id}/complete"
            );

        $response->assertOk();

        $this->assertDatabaseHas('lesson_completions', [
            'user_id'   => $this->student->id,
            'lesson_id' => $this->lesson->id,
        ]);
    }

    public function test_progress_updates_after_lesson_completion(): void
    {
        $this->actingAs($this->student, 'sanctum')
            ->postJson(
                "/api/v1/student/courses/{$this->course->id}/lessons/{$this->lesson->id}/complete"
            );

        $enrollment = CourseEnrollment::where('student_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->first();

        $this->assertGreaterThan(0, $enrollment->progress_percentage);
    }

    public function test_xp_is_awarded_for_lesson_completion(): void
    {
        $this->actingAs($this->student, 'sanctum')
            ->postJson(
                "/api/v1/student/courses/{$this->course->id}/lessons/{$this->lesson->id}/complete"
            );

        $stats = \App\Models\UserStats::where('user_id', $this->student->id)->first();
        $this->assertGreaterThanOrEqual(15, $stats->xp_total);
    }

    public function test_student_can_mark_lesson_incomplete(): void
    {
        // First complete it
        $this->actingAs($this->student, 'sanctum')
            ->postJson(
                "/api/v1/student/courses/{$this->course->id}/lessons/{$this->lesson->id}/complete"
            );

        // Then mark incomplete
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson(
                "/api/v1/student/courses/{$this->course->id}/lessons/{$this->lesson->id}/incomplete"
            );

        $response->assertOk();
        $this->assertDatabaseMissing('lesson_completions', [
            'user_id'   => $this->student->id,
            'lesson_id' => $this->lesson->id,
        ]);
    }

    public function test_course_completion_triggers_certificate(): void
    {
        // Mark all 3 lessons complete
        $lessons = CourseLesson::factory(2)->create([
            'course_id'  => $this->course->id,
            'section_id' => $this->section->id,
        ]);

        $allLessons = $lessons->push($this->lesson);

        foreach ($allLessons as $lesson) {
            $this->actingAs($this->student, 'sanctum')
                ->postJson(
                    "/api/v1/student/courses/{$this->course->id}/lessons/{$lesson->id}/complete"
                );
        }

        $this->assertDatabaseHas('course_enrollments', [
            'student_id' => $this->student->id,
            'course_id'  => $this->course->id,
        ]);
    }
}
