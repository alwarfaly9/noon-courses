<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddReviewRequest;
use App\Http\Requests\RejectCourseRequest;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Services\CourseService;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    public function __construct(
        private CourseService $courseService,
        private EnrollmentService $enrollmentService,
    ) {}

    public function index(Request $request)
    {
        $courses = $this->courseService->getFilteredCourses(
            $request->only(['category', 'level', 'search', 'page', 'min_price', 'max_price', 'min_rating', 'sort_by'])
        );

        return response()->json(['success' => true, 'data' => $courses]);
    }

    public function teacherIndex(Request $request)
    {
        $courses = $this->courseService->getTeacherCourses($request->user()->id);

        return response()->json(['success' => true, 'data' => $courses]);
    }

    public function newCourses(Request $request)
    {
        $courses = $this->courseService->getNewCourses($request->category);

        return response()->json(['success' => true, 'data' => $courses]);
    }

    public function show($id)
    {
        $course = $this->courseService->getCourseDetail((int) $id);

        return response()->json(['success' => true, 'data' => $course]);
    }

    public function enroll(Request $request, $courseId)
    {
        $user = $request->user();
        $courseCode = $request->input('coupon_code');

        try {
            $result = $this->enrollmentService->enroll($user, \App\Models\Course::findOrFail($courseId), $courseCode);

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

    public function myCourses(Request $request)
    {
        $courses = $this->courseService->getUserCourses($request->user());

        return response()->json(['success' => true, 'data' => $courses]);
    }

    public function getCourseContent(Request $request, $courseId)
    {
        $result = $this->courseService->getCourseContent($request->user(), (int) $courseId);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function getVideoUrl(Request $request, $lessonId)
    {
        $result = $this->courseService->getVideoUrl(
            $request->user(),
            (int) $lessonId,
            $request->getSchemeAndHttpHost()
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function addReview(AddReviewRequest $request, $courseId)
    {
        $review = $this->courseService->addReview(
            $request->user(),
            (int) $courseId,
            $request->integer('rating'),
            $request->input('review')
        );

        return response()->json([
            'success' => true,
            'message' => 'Review added successfully',
            'data' => $review,
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        $course = $this->courseService->createCourse($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => $course,
        ], 201);
    }

    public function update(UpdateCourseRequest $request, $id)
    {
        $course = $this->courseService->updateCourse($request->user(), (int) $id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully',
            'data' => $course,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->courseService->deleteCourse($request->user(), (int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully',
        ]);
    }

    public function pendingCourses(Request $request)
    {
        $courses = $this->courseService->getPendingCourses();

        return response()->json(['success' => true, 'data' => $courses]);
    }

    public function approveCourse(Request $request, $id)
    {
        $this->courseService->approveCourse($request->user(), (int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Course approved successfully',
        ]);
    }

    public function rejectCourse(RejectCourseRequest $request, $id)
    {
        $this->courseService->rejectCourse($request->user(), (int) $id, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Course rejected',
        ]);
    }
}
