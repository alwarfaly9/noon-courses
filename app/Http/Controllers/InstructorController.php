<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    /**
     * GET /instructors/{user}
     * Public instructor profile page.
     */
    public function show(User $user)
    {
        // Must be a teacher
        if (!$user->hasRole('teacher')) {
            return response()->json(['success' => false, 'message' => 'Instructor not found'], 404);
        }

        $courses = $user->coursesAsTeacher()
            ->where('status', 'published')
            ->withCount('enrollments')
            ->with(['category:id,name'])
            ->get(['id', 'title', 'thumbnail', 'price', 'discount_price',
                   'average_rating', 'category_id', 'level', 'teacher_id']);

        $studentCount = CourseEnrollment::whereIn('course_id', $courses->pluck('id'))->count();
        $avgRating    = $courses->whereNotNull('average_rating')->avg('average_rating');

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'avatar'               => $user->avatar,
                'bio'                  => $user->bio,
                'specialization'       => $user->specialization,
                'website'              => $user->website,
                'is_verified_instructor' => (bool) $user->is_verified_instructor,
                'stats'                => [
                    'courses'         => $courses->count(),
                    'students'        => $studentCount,
                    'average_rating'  => $avgRating ? round($avgRating, 1) : null,
                ],
                'courses'              => $courses,
            ],
        ]);
    }
}
