<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Services\NotificationService;
use App\Services\CacheService;
use App\Services\CommissionService;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function __construct(private EnrollmentService $enrollmentService)
    {
    }

    // Public: Get all published courses
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'level', 'search', 'page', 'min_price', 'max_price', 'min_rating', 'sort_by']);
        $cacheKey = CacheService::courseListKey($filters);

        $courses = CacheService::remember($cacheKey, CacheService::TTL_MEDIUM, function () use ($request) {
            $query = Course::where('status', 'published')
                ->with(['teacher:id,name,avatar', 'category:id,name'])
                ->withCount(['enrollments', 'reviews']);

            if ($request->category) {
                $query->where('category_id', $request->category);
            }
            if ($request->level) {
                $query->where('level', $request->level);
            }
            if ($request->min_price !== null) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->max_price !== null) {
                $query->where('price', '<=', $request->max_price);
            }
            if ($request->min_rating !== null) {
                $query->where('rating', '>=', $request->min_rating);
            }
            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            $sortBy = $request->input('sort_by', 'newest');
            match ($sortBy) {
                'price_asc' => $query->orderBy('price', 'asc'),
                'price_desc' => $query->orderBy('price', 'desc'),
                'rating_desc' => $query->orderBy('rating', 'desc'),
                'popular' => $query->orderBy('enrollments_count', 'desc'),
                default => $query->orderBy('created_at', 'desc'),
            };

            return $query->paginate(20);
        });

        return response()->json(['success' => true, 'data' => $courses]);
    }

    // Teacher: Get my courses
    public function teacherIndex(Request $request)
    {
        $user = $request->user();
        $courses = Course::where('teacher_id', $user->id)
            ->with(['category:id,name', 'sections.lessons'])
            ->withCount(['enrollments', 'reviews'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    // Public: Get new courses (recently added)
    public function newCourses(Request $request)
    {
        $query = Course::where('status', 'published')
            ->with(['teacher:id,name,avatar', 'category:id,name'])
            ->withCount(['enrollments', 'reviews'])
            ->orderBy('created_at', 'desc')
            ->limit(10);

        // Optional category filter
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        $courses = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    // Public: Get course details
    public function show($id)
    {
        $course = CacheService::remember(
            CacheService::courseKey((int) $id),
            CacheService::TTL_STANDARD,
            function () use ($id) {
                $c = Course::with([
                    'teacher:id,name,avatar,bio,specialization,is_verified_instructor',
                    'category:id,name,icon',
                    'sections'          => fn($q) => $q->orderBy('order'),
                    'sections.lessons:id,section_id,title,duration,is_free,order',
                    'sections.quizzes:id,section_id,title',
                ])
                ->withCount(['enrollments', 'reviews'])
                ->findOrFail($id);

                // Load only top 5 approved reviews to keep payload small
                $c->setRelation('reviews',
                    $c->reviews()
                        ->where('is_approved', true)
                        ->with('user:id,name,avatar')
                        ->orderByDesc('helpful_votes')
                        ->limit(5)
                        ->get()
                );

                return $c;
            }
        );

        return response()->json(['success' => true, 'data' => $course]);
    }

    // Student: Enroll in course
    public function enroll(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);
        $couponCode = $request->input('coupon_code');

        try {
            $result = $this->enrollmentService->enroll($user, $course, $couponCode);

            return response()->json([
                'success' => true,
                'message' => 'Enrolled successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $code = str_contains($e->getMessage(), 'Already enrolled') ? 400
                : (str_contains($e->getMessage(), 'Insufficient') ? 400 : 500);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    // Student: Get my enrolled courses (list view — no full section tree)
    public function myCourses(Request $request)
    {
        $user = $request->user();

        $courses = $user->enrolledCourses()
            ->with([
                'teacher:id,name,avatar',
                'category:id,name',
            ])
            ->withCount('lessons')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $courses,
        ]);
    }

    // Student: Get course content
    public function getCourseContent(Request $request, $courseId)
    {
        $user = $request->user();

        // Check enrollment
        $enrollment = CourseEnrollment::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Not enrolled in this course'
            ], 403);
        }

        $course = Course::with(['sections.lessons', 'teacher', 'category'])
            ->findOrFail($courseId);

        return response()->json([
            'success' => true,
            'data' => [
                'course' => $course,
                'enrollment' => $enrollment
            ]
        ]);
    }

    // Student: Get a time-limited streaming URL for a lesson video.
    //
    // Returns a URL pointing to VideoStreamController::stream() that carries
    // an AES-encrypted token as a query parameter. This approach:
    //  - Works on any device/IP (no dependency on APP_URL)
    //  - Avoids Laravel signed-route HMAC mismatches (APP_URL != request host)
    //  - Lets native video players (AVPlayer / ExoPlayer) open the URL
    //    without custom Authorization headers
    public function getVideoUrl(Request $request, $lessonId)
    {
        $user   = $request->user();
        $lesson = CourseLesson::with('course')->findOrFail($lessonId);

        $isEnrolled = CourseEnrollment::where('student_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->exists();

        if (!$isEnrolled && !$lesson->is_preview) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to access this lesson video',
            ], 403);
        }

        $storedPath = $lesson->content_url ?: $lesson->content_file;

        if (!$storedPath) {
            return response()->json([
                'success' => false,
                'message' => 'No video found for this lesson',
            ], 404);
        }

        // External URL (YouTube / Vimeo embed) - return as-is
        if (Str::startsWith($storedPath, ['http://', 'https://'])) {
            return response()->json([
                'success' => true,
                'data'    => ['video_url' => $storedPath],
            ]);
        }

        if (!Storage::disk('private')->exists($storedPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Video file is missing from storage',
            ], 404);
        }

        // Build encrypted, time-limited token (120-minute TTL)
        $token = Crypt::encryptString(json_encode([
            'lesson_id' => $lesson->id,
            'user_id'   => $user->id,
            'expires'   => now()->addMinutes(120)->timestamp,
        ]));

        // Use the actual host from the incoming request so the URL is always
        // reachable on the same network interface the client is already using.
        $baseUrl  = $request->getSchemeAndHttpHost();
        $videoUrl = "{$baseUrl}/api/v1/video/stream/{$lessonId}?token=" . urlencode($token);

        return response()->json([
            'success' => true,
            'data'    => ['video_url' => $videoUrl],
        ]);
    }

    // Student: Add review
    public function addReview(Request $request, $courseId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if already reviewed
        if (\App\Models\CourseReview::where('course_id', $courseId)
            ->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Already reviewed this course'
            ], 409);
        }

        $course = Course::findOrFail($courseId);

        $review = \App\Models\CourseReview::create([
            'course_id' => $courseId,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        // Notify Teacher
        NotificationService::send(
            $course->teacher,
            'New Review Received',
            "Your course {$course->title} received a {$request->rating}-star review.",
            'review',
            ['course_id' => $course->id, 'review_id' => $review->id]
        );

        // Update course rating
        $avgRating = \App\Models\CourseReview::where('course_id', $courseId)->avg('rating');
        $course->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $course->reviews_count + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review added successfully',
            'data' => $review
        ]);
    }

    // Teacher: Create course
    public function store(\App\Http\Requests\StoreCourseRequest $request)
    {
        $user = $request->user();

        $data = $request->validated();
        $data['teacher_id'] = $user->id;
        $data['slug'] = \Str::slug($request->title);
        $data['status'] = 'pending';
        $data['language'] = $request->language ?? 'ar';

        // Handle File Uploads
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('courses/images', 'public');
        }

        if ($request->hasFile('video_intro')) {
            $data['video_intro'] = $request->file('video_intro')->store('courses/videos', 'public');
        }

        $course = Course::create($data);

        // Notify Admins
        NotificationService::sendToAdmins(
            'New Course Submitted',
            "Teacher {$user->name} has submitted a new course: {$course->title}",
            'system',
            ['course_id' => $course->id]
        );

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'create_course',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Created course: {$course->title}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => $course
        ], 201);
    }

    // Teacher: Update course
    public function update(\App\Http\Requests\UpdateCourseRequest $request, $id)
    {
        $course = Course::findOrFail($id);

        // Check ownership or admin
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = collect($request->validated())->except(['image', 'video_intro'])->toArray(); // Handle files separately

        // Handle File Uploads
        if ($request->hasFile('image')) {
            if ($course->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($course->image);
            }
            $data['image'] = $request->file('image')->store('courses/images', 'public');
        }

        if ($request->hasFile('video_intro')) {
            if ($course->video_intro) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($course->video_intro);
            }
            $data['video_intro'] = $request->file('video_intro')->store('courses/videos', 'public');
        }

        $course->update($data);
        CacheService::invalidateCourse($course->id);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully',
            'data' => $course
        ]);
    }

    // Teacher: Delete course
    public function destroy(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $course->update(['status' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully'
        ]);
    }

    // Admin: Get pending courses
    public function pendingCourses(Request $request)
    {
        $courses = Course::where('status', 'pending')
            ->with(['teacher', 'category'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    // Admin: Approve course
    public function approveCourse(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        
        $course->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        CacheService::invalidateCourse($course->id);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'approve_course',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Approved course: {$course->title}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Course approved successfully'
        ]);
    }

    // Admin: Reject course
    public function rejectCourse(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $course = Course::findOrFail($id);
        
        $course->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);
        CacheService::invalidateCourse($course->id);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'reject_course',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Rejected course: {$course->title}. Reason: {$request->reason}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Course rejected'
        ]);
    }
}
