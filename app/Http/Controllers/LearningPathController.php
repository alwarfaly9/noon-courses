<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Services\LearningPathService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class LearningPathController extends Controller
{
    public function __construct(private readonly LearningPathService $service) {}

    // ── Public ────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/learning-paths
     */
    public function index(Request $request)
    {
        $cacheKey = 'learning_paths_' . md5(serialize($request->only(['category_id', 'difficulty', 'page'])));

        $paths = Cache::remember($cacheKey, 180, function () use ($request) {
            $query = LearningPath::published()
                ->with(['creator:id,name,avatar', 'category:id,name'])
                ->withCount('enrollments');

            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->difficulty) {
                $query->where('difficulty_level', $request->difficulty);
            }
            if ($request->featured) {
                $query->featured();
            }

            return $query->latest()->paginate(12);
        });

        return response()->json(['success' => true, 'data' => $paths]);
    }

    /**
     * GET /api/v1/learning-paths/{slug}
     */
    public function show(string $slug)
    {
        $path = LearningPath::published()
            ->with([
                'creator:id,name,avatar,bio',
                'category:id,name',
                'courses' => fn($q) => $q->select(
                    'courses.id', 'title', 'image', 'level', 'duration',
                    'lectures_count', 'rating', 'students_count', 'teacher_id'
                )->with('teacher:id,name'),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $path]);
    }

    // ── Authenticated ─────────────────────────────────────────────────────────

    /**
     * POST /api/v1/learning-paths/{id}/enroll
     */
    public function enroll(Request $request, LearningPath $learningPath)
    {
        try {
            $enrollment = $this->service->enroll($request->user(), $learningPath);

            return response()->json([
                'success' => true,
                'message' => 'تم التسجيل في المسار بنجاح',
                'data'    => $enrollment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/v1/student/learning-paths
     */
    public function myPaths(Request $request)
    {
        $enrollments = LearningPathEnrollment::where('user_id', $request->user()->id)
            ->with([
                'learningPath:id,title,slug,thumbnail,difficulty_level,estimated_hours,courses_count',
            ])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['success' => true, 'data' => $enrollments]);
    }

    /**
     * GET /api/v1/student/learning-paths/{id}/progress
     */
    public function progress(Request $request, LearningPath $learningPath)
    {
        $progress = $this->service->recalculateProgress($request->user(), $learningPath);

        $enrollment = LearningPathEnrollment::where('learning_path_id', $learningPath->id)
            ->where('user_id', $request->user()->id)
            ->first();

        // Annotate which courses are completed
        $courseIds = $learningPath->courses()->pluck('courses.id');
        $completedCourseIds = \App\Models\CourseEnrollment::where('student_id', $request->user()->id)
            ->whereIn('course_id', $courseIds)
            ->where('progress_percentage', 100)
            ->pluck('course_id');

        return response()->json([
            'success' => true,
            'data' => [
                'progress_percentage'  => $progress,
                'status'               => $enrollment?->status,
                'completed_course_ids' => $completedCourseIds,
                'enrolled_at'          => $enrollment?->enrolled_at,
                'completed_at'         => $enrollment?->completed_at,
            ],
        ]);
    }

    // ── Teacher ───────────────────────────────────────────────────────────────

    /**
     * POST /api/v1/teacher/learning-paths
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'            => 'required|string|max:200',
            'description'      => 'nullable|string',
            'category_id'      => 'nullable|exists:categories,id',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'estimated_hours'  => 'nullable|integer|min:1|max:9999',
            'skill_tags'       => 'nullable|array',
            'skill_tags.*'     => 'string|max:50',
            'course_ids'       => 'nullable|array',
            'course_ids.*'     => 'exists:courses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $path = $this->service->createPath($request->user(), $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء مسار التعلم',
                'data'    => $path->load(['courses', 'category']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * PUT /api/v1/teacher/learning-paths/{id}
     */
    public function update(Request $request, LearningPath $learningPath)
    {
        $this->authorizeOwner($request->user(), $learningPath);

        $data = $request->validate([
            'title'            => 'sometimes|string|max:200',
            'description'      => 'nullable|string',
            'difficulty_level' => 'sometimes|in:beginner,intermediate,advanced',
            'estimated_hours'  => 'nullable|integer|min:1',
            'skill_tags'       => 'nullable|array',
            'course_ids'       => 'nullable|array',
            'course_ids.*'     => 'exists:courses,id',
            'status'           => 'sometimes|in:draft,published',
        ]);

        if (isset($data['course_ids'])) {
            $this->service->syncCourses($learningPath, $data['course_ids']);
            unset($data['course_ids']);
        }

        $learningPath->update($data);
        Cache::flush(); // Invalidate path list caches

        return response()->json(['success' => true, 'data' => $learningPath->fresh()->load('courses')]);
    }

    /**
     * DELETE /api/v1/teacher/learning-paths/{id}
     */
    public function destroy(Request $request, LearningPath $learningPath)
    {
        $this->authorizeOwner($request->user(), $learningPath);
        $learningPath->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المسار']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeOwner($user, LearningPath $path): void
    {
        if ($path->created_by !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح');
        }
    }
}
