<?php

namespace App\Services;

use App\Events\CourseApproved;
use App\Events\CourseRejected;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseReview;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseService
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function getFilteredCourses(array $filters): mixed
    {
        $cacheKey = CacheService::courseListKey($filters);

        return CacheService::remember($cacheKey, CacheService::TTL_MEDIUM, function () use ($filters) {
            $query = Course::where('status', 'published')
                ->with(['teacher:id,name,avatar', 'category:id,name'])
                ->withCount(['enrollments', 'reviews']);

            if (!empty($filters['category'])) {
                $query->where('category_id', $filters['category']);
            }
            if (!empty($filters['level'])) {
                $query->where('level', $filters['level']);
            }
            if (isset($filters['min_price']) && $filters['min_price'] !== null) {
                $query->where('price', '>=', $filters['min_price']);
            }
            if (isset($filters['max_price']) && $filters['max_price'] !== null) {
                $query->where('price', '<=', $filters['max_price']);
            }
            if (isset($filters['min_rating']) && $filters['min_rating'] !== null) {
                $query->where('rating', '>=', $filters['min_rating']);
            }
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $sortBy = $filters['sort_by'] ?? 'newest';
            match ($sortBy) {
                'price_asc' => $query->orderBy('price', 'asc'),
                'price_desc' => $query->orderBy('price', 'desc'),
                'rating_desc' => $query->orderBy('rating', 'desc'),
                'popular' => $query->orderBy('enrollments_count', 'desc'),
                default => $query->orderBy('created_at', 'desc'),
            };

            return $query->paginate(20);
        });
    }

    public function getTeacherCourses(int $teacherId): mixed
    {
        return Course::where('teacher_id', $teacherId)
            ->with(['category:id,name', 'sections.lessons'])
            ->withCount(['enrollments', 'reviews'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getNewCourses(?int $categoryId = null): mixed
    {
        $query = Course::where('status', 'published')
            ->with(['teacher:id,name,avatar', 'category:id,name'])
            ->withCount(['enrollments', 'reviews'])
            ->orderBy('created_at', 'desc');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->paginate(10);
    }

    public function getCourseDetail(int $courseId): Course
    {
        return CacheService::remember(
            CacheService::courseKey($courseId),
            CacheService::TTL_STANDARD,
            function () use ($courseId) {
                $c = Course::with([
                    'teacher:id,name,avatar,bio,specialization,is_verified_instructor',
                    'category:id,name,icon',
                    'sections' => fn($q) => $q->orderBy('order'),
                    'sections.lessons:id,section_id,title,duration,is_free,order',
                    'sections.quizzes:id,section_id,title',
                ])
                ->withCount(['enrollments', 'reviews'])
                ->findOrFail($courseId);

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
    }

    public function getUserCourses(User $user): mixed
    {
        return $user->enrolledCourses()
            ->with(['teacher:id,name,avatar', 'category:id,name'])
            ->withCount('lessons')
            ->paginate(20);
    }

    public function getCourseContent(User $user, int $courseId): array
    {
        $enrollment = CourseEnrollment::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$enrollment) {
            abort(403, 'Not enrolled in this course');
        }

        $course = Course::with(['sections.lessons', 'teacher', 'category'])
            ->findOrFail($courseId);

        return ['course' => $course, 'enrollment' => $enrollment];
    }

    public function getVideoUrl(User $user, int $lessonId, string $schemeAndHost): array
    {
        $lesson = CourseLesson::with('course')->findOrFail($lessonId);

        $isEnrolled = CourseEnrollment::where('student_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->exists();

        if (!$isEnrolled && !$lesson->is_preview) {
            abort(403, 'Not authorized to access this lesson video');
        }

        $storedPath = $lesson->content_url ?: $lesson->content_file;

        if (!$storedPath) {
            abort(404, 'No video found for this lesson');
        }

        if (Str::startsWith($storedPath, ['http://', 'https://'])) {
            return ['video_url' => $storedPath];
        }

        if (!Storage::disk('private')->exists($storedPath)) {
            abort(404, 'Video file is missing from storage');
        }

        $token = Crypt::encryptString(json_encode([
            'lesson_id' => $lesson->id,
            'user_id'   => $user->id,
            'expires'   => now()->addMinutes(120)->timestamp,
        ]));

        $videoUrl = "{$schemeAndHost}/api/v1/video/stream/{$lessonId}?token=" . urlencode($token);

        return ['video_url' => $videoUrl];
    }

    public function addReview(User $user, int $courseId, int $rating, ?string $review): CourseReview
    {
        if (CourseReview::where('course_id', $courseId)->where('user_id', $user->id)->exists()) {
            abort(409, 'Already reviewed this course');
        }

        $course = Course::findOrFail($courseId);

        $reviewModel = CourseReview::create([
            'course_id' => $courseId,
            'user_id' => $user->id,
            'rating' => $rating,
            'review' => $review,
        ]);

        $this->notificationService->send(
            $course->teacher,
            'New Review Received',
            "Your course {$course->title} received a {$rating}-star review.",
            'review',
            ['course_id' => $course->id, 'review_id' => $reviewModel->id]
        );

        $avgRating = CourseReview::where('course_id', $courseId)->avg('rating');
        $course->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $course->reviews_count + 1,
        ]);

        return $reviewModel;
    }

    public function createCourse(User $user, array $data): Course
    {
        $data['teacher_id'] = $user->id;
        $data['slug'] = Str::slug($data['title']);
        $data['status'] = 'pending';
        $data['language'] = $data['language'] ?? 'ar';

        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['image'] = $data['image']->store('courses/images', 'public');
        }

        if (isset($data['video_intro']) && $data['video_intro'] instanceof \Illuminate\Http\UploadedFile) {
            $data['video_intro'] = $data['video_intro']->store('courses/videos', 'public');
        }

        $course = Course::create($data);

        $this->notificationService->sendToAdmins(
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
            'ip_address' => request()->ip(),
        ]);

        return $course;
    }

    public function updateCourse(User $user, int $courseId, array $data): Course
    {
        $course = Course::findOrFail($courseId);

        if ($course->teacher_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $imageFile = $data['image'] ?? null;
        $videoFile = $data['video_intro'] ?? null;
        unset($data['image'], $data['video_intro']);

        if ($imageFile instanceof \Illuminate\Http\UploadedFile) {
            if ($course->image) {
                Storage::disk('public')->delete($course->image);
            }
            $data['image'] = $imageFile->store('courses/images', 'public');
        }

        if ($videoFile instanceof \Illuminate\Http\UploadedFile) {
            if ($course->video_intro) {
                Storage::disk('public')->delete($course->video_intro);
            }
            $data['video_intro'] = $videoFile->store('courses/videos', 'public');
        }

        $course->update($data);
        CacheService::invalidateCourse($course->id);

        return $course;
    }

    public function deleteCourse(User $user, int $courseId): void
    {
        $course = Course::findOrFail($courseId);

        if ($course->teacher_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $course->update(['status' => 'deleted']);
    }

    public function getPendingCourses(): mixed
    {
        return Course::where('status', 'pending')
            ->with(['teacher', 'category'])
            ->paginate(15);
    }

    public function approveCourse(User $user, int $courseId): void
    {
        $course = Course::findOrFail($courseId);

        $course->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        CacheService::invalidateCourse($course->id);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'approve_course',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Approved course: {$course->title}",
            'ip_address' => request()->ip(),
        ]);

        CourseApproved::dispatch($course);
    }

    public function rejectCourse(User $user, int $courseId, string $reason): void
    {
        $course = Course::findOrFail($courseId);

        $course->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        CacheService::invalidateCourse($course->id);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'reject_course',
            'model_type' => 'Course',
            'model_id' => $course->id,
            'description' => "Rejected course: {$course->title}. Reason: {$reason}",
            'ip_address' => request()->ip(),
        ]);

        CourseRejected::dispatch($course, $reason);
    }
}
