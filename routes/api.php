<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PersonalizedDashboardController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\LearningAnalyticsController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\SuccessStoryController;
use App\Http\Controllers\StoryApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// ──────────────────────────────────────────────────────────
// API v1
// ──────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

// Public Routes
Route::get('health', [\App\Http\Controllers\HealthController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1,reg_');
    Route::post('send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:5,1,otp_');
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:5,1,votp_');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1,login_');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1,fpwd_');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1,rpwd_');
});

// Categories - Public
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);

// Instructors - Public
Route::get('instructors/{user}', [InstructorController::class, 'show']);

// Banners - Public
Route::get('banners', [BannerController::class, 'index']);

// Success Stories - Public
Route::get('success-stories', [SuccessStoryController::class, 'index']);

// Courses - Public
Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/new', [CourseController::class, 'newCourses']);
Route::get('courses/{course}', [CourseController::class, 'show']);

// Video streaming – token-based auth (no Bearer header required so native
// video players can open the URL directly). The encrypted token issued by
// getVideoUrl() carries lesson_id, user_id, and an expiry timestamp.
Route::get('video/stream/{lesson}', [\App\Http\Controllers\VideoStreamController::class, 'stream']);
Route::options('video/stream/{lesson}', [\App\Http\Controllers\VideoStreamController::class, 'stream']);

// Certificate verification — public so anyone can verify a certificate link
Route::get('certificates/verify/{id}', [\App\Http\Controllers\CertificateController::class, 'verify']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware('token.ability:refresh,access');
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
    });

    // ── Referrals ──────────────────────────────────────────────────────────
    Route::prefix('referrals')->group(function () {
        Route::get('/',          [ReferralController::class, 'stats']);
        Route::post('generate',  [ReferralController::class, 'generate']);
    });

    // ── Reviews (edit/delete/helpful — addReview stays in CourseController) ──
    Route::put('reviews/{review}',           [ReviewController::class, 'update']);
    Route::delete('reviews/{review}',        [ReviewController::class, 'destroy']);
    Route::post('reviews/{review}/helpful',  [ReviewController::class, 'toggleHelpful']);

    // ── Campaigns ──────────────────────────────────────────────────────────
    Route::prefix('campaigns')->group(function () {
        Route::get('/',                          [CampaignController::class, 'index']);
        Route::post('{campaign}/join',           [CampaignController::class, 'join']);
    });

    // ── Success Stories (student submission) ──────────────────────────────
    Route::post('success-stories', [SuccessStoryController::class, 'store']);

    // Device Token (Push Notifications)
    Route::post('device-tokens', [\App\Http\Controllers\DeviceTokenController::class, 'store']);
    Route::delete('device-tokens', [\App\Http\Controllers\DeviceTokenController::class, 'destroy']);

    // Notification Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::get('/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount']);
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy']);
    });

    // Student Routes
    Route::prefix('student')->group(function () {
        // Course enrollments
        Route::post('courses/{course}/enroll', [CourseController::class, 'enroll']);
        Route::get('my-courses', [CourseController::class, 'myCourses']);
        Route::get('courses/{course}', [CourseController::class, 'getCourseContent']);
        Route::get('lessons/{lesson}/video-url', [CourseController::class, 'getVideoUrl']);
        
        // Progress
        Route::post('courses/{course}/lessons/{lesson}/complete', [\App\Http\Controllers\StudentProgressController::class, 'markLessonComplete']);
        Route::post('courses/{course}/lessons/{lesson}/incomplete', [\App\Http\Controllers\StudentProgressController::class, 'markLessonIncomplete']);
        
        // Reviews
        Route::post('courses/{course}/reviews', [CourseController::class, 'addReview']);

        // Coupons
        Route::post('coupons/validate', [PaymentController::class, 'validateCoupon']);
        
        // Quizzes (New System)
        Route::get('quizzes/{id}', [\App\Http\Controllers\QuizController::class, 'show']);
        Route::post('quizzes/{id}/submit', [\App\Http\Controllers\QuizController::class, 'submit']);
        Route::get('quiz-attempts/{id}', [\App\Http\Controllers\QuizController::class, 'results']);
        
        // Certificates
        Route::get('courses/{course}/certificate', [\App\Http\Controllers\CertificateController::class, 'getCertificate']);

        // Stories
        Route::get('stories', [StoryApiController::class, 'index']);
        Route::post('stories/{story}/view', [StoryApiController::class, 'recordView']);
    });

    // Public Certificate Routes (download requires auth; verify is public above)
    Route::get('certificates/{id}/download', [\App\Http\Controllers\CertificateController::class, 'download']);

    // Teacher Routes
    Route::prefix('teacher')->middleware('permission:manage_own_courses')->group(function () {
        Route::get('courses', [CourseController::class, 'teacherIndex']);
        Route::apiResource('courses', CourseController::class)->except(['index']);
        Route::get('dashboard', [DashboardController::class, 'teacherDashboard']);
        
        // Course Content Management
        Route::post('courses/{course}/sections', [\App\Http\Controllers\CourseContentController::class, 'storeSection']);
        Route::put('courses/{course}/sections/{section}', [\App\Http\Controllers\CourseContentController::class, 'updateSection']);
        Route::delete('courses/{course}/sections/{section}', [\App\Http\Controllers\CourseContentController::class, 'deleteSection']);
        Route::post('courses/{course}/sections/reorder', [\App\Http\Controllers\CourseContentController::class, 'reorderSections']);
        
        Route::post('courses/{course}/sections/{section}/lessons', [\App\Http\Controllers\CourseContentController::class, 'storeLesson']);
        Route::post('courses/{course}/sections/{section}/lessons/{lesson}', [\App\Http\Controllers\CourseContentController::class, 'updateLesson']); // Using POST for file upload support
        Route::delete('courses/{course}/sections/{section}/lessons/{lesson}', [\App\Http\Controllers\CourseContentController::class, 'deleteLesson']);
        Route::post('courses/{course}/sections/{section}/lessons/reorder', [\App\Http\Controllers\CourseContentController::class, 'reorderLessons']);
        
        // Quiz Management
        Route::post('lessons/{lesson}/questions', [\App\Http\Controllers\QuizController::class, 'storeQuestion']);
        Route::delete('questions/{question}', [\App\Http\Controllers\QuizController::class, 'deleteQuestion']);

        // Withdraw Requests (Teacher)
        Route::get('withdraw-requests', [PaymentController::class, 'myWithdrawRequests']);
        Route::post('withdraw-requests', [PaymentController::class, 'requestWithdraw']);

        // Analytics
        Route::get('analytics', [\App\Http\Controllers\TeacherAnalyticsApiController::class, 'index']);
    });

    // Payment Routes
    Route::prefix('payment')->group(function () {
        Route::get('credit-balance', [PaymentController::class, 'getCreditBalance']);
        Route::post('credit-cards/redeem', [PaymentController::class, 'redeemCreditCard']);
        Route::get('transactions', [PaymentController::class, 'myTransactions']);
    });

    // User Routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::post('profile', [UserController::class, 'update']);
        // Notifications served by NotificationController at /notifications — these are legacy duplicates
    });

    // Support Tickets
    Route::prefix('support')->group(function () {
        Route::get('tickets', [SupportTicketController::class, 'index']);
        Route::post('tickets', [SupportTicketController::class, 'store']);
        Route::get('tickets/{ticket}', [SupportTicketController::class, 'show']);
    });

    // ── Gamification (auth) ───────────────────────────────────────────────
    Route::prefix('gamification')->group(function () {
        Route::get('stats',       [GamificationController::class, 'stats']);
        Route::get('badges',      [GamificationController::class, 'badges']);
        Route::get('leaderboard', [GamificationController::class, 'leaderboard']);
        Route::get('streaks',     [GamificationController::class, 'streaks']);
    });

    // ── Personalized Dashboard ────────────────────────────────────────────
    Route::get('student/dashboard',             [PersonalizedDashboardController::class, 'index']);
    Route::patch('student/dashboard/goals',     [PersonalizedDashboardController::class, 'updateGoals']);

    // ── Notification Preferences ──────────────────────────────────────────
    Route::get('student/notification-preferences',   [NotificationPreferenceController::class, 'show']);
    Route::patch('student/notification-preferences', [NotificationPreferenceController::class, 'update']);

    // ── Onboarding ────────────────────────────────────────────────────────
    Route::prefix('onboarding')->middleware('throttle:30,1')->group(function () {
        Route::get('categories',      [OnboardingController::class, 'categories']);
        Route::get('skills',          [OnboardingController::class, 'skills']);
        Route::post('complete',       [OnboardingController::class, 'complete']);
        Route::get('recommendations', [OnboardingController::class, 'recommendations']);
    });

    // ── Student Analytics ─────────────────────────────────────────────────
    Route::get('student/analytics', [LearningAnalyticsController::class, 'index']);

    // ── Skills (auth) ─────────────────────────────────────────────────────
    Route::get('user/skills',          [SkillController::class, 'mySkills']);
    Route::get('skills/recommended',   [SkillController::class, 'recommended']);

    // ── Learning Paths (student, auth) ────────────────────────────────────
    Route::post('learning-paths/{learningPath}/enroll', [LearningPathController::class, 'enroll']);
    Route::prefix('student/learning-paths')->group(function () {
        Route::get('/',          [LearningPathController::class, 'myPaths']);
        Route::get('{learningPath}/progress', [LearningPathController::class, 'progress']);
    });

    // ── Learning Paths (teacher) ──────────────────────────────────────────
    Route::prefix('teacher/learning-paths')->middleware('permission:manage_own_courses')->group(function () {
        Route::get('/',         [LearningPathController::class, 'teacherIndex'] ?? [LearningPathController::class, 'index']);
        Route::post('/',        [LearningPathController::class, 'store']);
        Route::put('{learningPath}',    [LearningPathController::class, 'update']);
        Route::delete('{learningPath}', [LearningPathController::class, 'destroy']);
    });

    // ── Community — lesson comments (auth — write only; GET is public below) ──
    Route::post('lessons/{lessonId}/comments', [CommunityController::class, 'store']);
    Route::prefix('comments/{comment}')->group(function () {
        Route::delete('/',  [CommunityController::class, 'destroy']);
        Route::post('like', [CommunityController::class, 'like']);
        // Rate-limited: max 5 reports per user per minute to prevent spam
        Route::post('report', [CommunityController::class, 'report'])->middleware('throttle:5,1');
    });
});

// ── Public routes (no auth) ───────────────────────────────────────────────────

// Learning Paths public list + detail
Route::prefix('v1')->group(function () {
    Route::get('learning-paths',         [LearningPathController::class, 'index']);
    Route::get('learning-paths/{slug}',  [LearningPathController::class, 'show'])
         ->where('slug', '[a-z0-9\-]+');

    // Skills public endpoints
    Route::get('skills',          [SkillController::class, 'index']);
    Route::get('skills/trending', [SkillController::class, 'trending']);

    // Community read-only (unauth guests can read comments)
    Route::get('lessons/{lessonId}/comments', [CommunityController::class, 'index']);
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'permission:access_admin_panel'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'adminDashboard']);
    
    // Users Management
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'updateUser']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::post('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    
    // Courses Management
    Route::get('courses/pending', [CourseController::class, 'pendingCourses']);
    Route::post('courses/{course}/approve', [CourseController::class, 'approveCourse']);
    Route::post('courses/{course}/reject', [CourseController::class, 'rejectCourse']);
    
    // Categories Management
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
    
    // Payments & Transactions
    Route::get('transactions', [PaymentController::class, 'allTransactions']);
    Route::get('withdraw-requests', [PaymentController::class, 'withdrawRequests']);
    Route::post('withdraw-requests/{request}/approve', [PaymentController::class, 'approveWithdraw']);
    Route::post('withdraw-requests/{request}/reject', [PaymentController::class, 'rejectWithdraw']);
    
    // Credit Cards Management
    Route::get('credit-cards', [PaymentController::class, 'creditCards']);
    Route::post('credit-cards/generate', [PaymentController::class, 'generateCreditCards']);
    
    // Coupons Management
    Route::get('coupons', [PaymentController::class, 'coupons']);
    Route::post('coupons', [PaymentController::class, 'createCoupon']);
    Route::put('coupons/{id}', [PaymentController::class, 'updateCoupon']);
    Route::delete('coupons/{id}', [PaymentController::class, 'deleteCoupon']);
    
    // Support Tickets Management
    Route::get('support/tickets', [SupportTicketController::class, 'adminIndex']);
    Route::post('support/tickets/{ticket}/assign', [SupportTicketController::class, 'assign']);
    Route::post('support/tickets/{ticket}/resolve', [SupportTicketController::class, 'resolve']);
    
    // Roles & Permissions
    Route::get('roles', [AdminController::class, 'getRoles']);
    Route::post('roles', [AdminController::class, 'createRole']);
    Route::put('roles/{id}', [AdminController::class, 'updateRole']);
    Route::delete('roles/{id}', [AdminController::class, 'deleteRole']);
    Route::get('permissions', [AdminController::class, 'getPermissions']);
    
    // Settings
    Route::get('settings', [AdminController::class, 'getSettings']);
    Route::post('settings', [AdminController::class, 'updateSettings']);
    
    // Analytics & Reports
    Route::get('analytics/overview', [DashboardController::class, 'analytics']);
    Route::get('analytics/summary',  [AdminAnalyticsController::class, 'summary']);
    Route::get('analytics/growth',   [AdminAnalyticsController::class, 'growth']);
    Route::get('analytics/learning', [AdminAnalyticsController::class, 'learning']);

    // Activity Logs
    Route::get('activity-logs', [AdminController::class, 'activityLogs']);

    // ── Admin: Learning Paths moderation ──────────────────────────────────
    Route::prefix('learning-paths')->group(function () {
        Route::get('/',                         [LearningPathController::class, 'index']);
        Route::post('{learningPath}/publish',   fn(\App\Models\LearningPath $lp) =>
            tap($lp->update(['status' => 'published']), fn() => response()->json(['success' => true]))
        );
        Route::delete('{learningPath}', [LearningPathController::class, 'destroy']);
    });

    // ── Admin: Community moderation ───────────────────────────────────────
    Route::prefix('comments')->group(function () {
        Route::get('/',          [CommunityController::class, 'adminIndex']);
        Route::post('{comment}/approve', [CommunityController::class, 'approve']);
        Route::delete('{comment}',       [CommunityController::class, 'adminDestroy']);
    });

    // ── Admin: Campaigns ──────────────────────────────────────────────────
    Route::prefix('campaigns')->group(function () {
        Route::get('/',                  [CampaignController::class, 'adminIndex']);
        Route::post('/',                 [CampaignController::class, 'adminStore']);
        Route::put('{campaign}',         [CampaignController::class, 'adminUpdate']);
        Route::delete('{campaign}',      [CampaignController::class, 'adminDestroy']);
    });

    // ── Admin: Banners ────────────────────────────────────────────────────
    Route::prefix('banners')->group(function () {
        Route::get('/',              [BannerController::class, 'adminIndex']);
        Route::post('/',             [BannerController::class, 'adminStore']);
        Route::put('{banner}',       [BannerController::class, 'adminUpdate']);
        Route::delete('{banner}',    [BannerController::class, 'adminDestroy']);
    });

    // ── Admin: Success Stories ────────────────────────────────────────────
    Route::prefix('success-stories')->group(function () {
        Route::get('/',                       [SuccessStoryController::class, 'adminIndex']);
        Route::post('{story}/approve',        [SuccessStoryController::class, 'adminApprove']);
        Route::post('{story}/feature',        [SuccessStoryController::class, 'adminFeature']);
        Route::delete('{story}',              [SuccessStoryController::class, 'adminDestroy']);
    });

    // ── Admin: Reviews ────────────────────────────────────────────────────
    Route::post('reviews/{review}/feature',   [ReviewController::class, 'feature']);
});

// Client Analytics Events (telemetry from Flutter app)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('analytics/events', function (\Illuminate\Http\Request $request) {
        return response()->json(['success' => true, 'received' => count($request->input('events', []))]);
    })->middleware('throttle:30,1');

    // Chat Routes (previously missing — now registered)
    Route::prefix('chat')->group(function () {
        Route::get('conversations',                 [ChatController::class, 'index']);
        Route::post('conversations/private',        [ChatController::class, 'startPrivateChat']);
        Route::get('conversations/{id}/messages',   [ChatController::class, 'show']);
        Route::post('conversations/{id}/messages',  [ChatController::class, 'store']);
        Route::post('conversations/{id}/read',      [ChatController::class, 'markRead']);
    });
});
});

Route::any('{any}', function (Illuminate\Http\Request $request) {
    return redirect('/api/v1/' . $request->path(), 307);
})->where('any', '(?!v1/).*');
