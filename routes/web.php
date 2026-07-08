<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseContentController as AdminCourseContentController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\AchievementController as AdminAchievementController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\StoryController as AdminStoryController;
use App\Http\Controllers\Admin\ReferralController as AdminReferralController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Teacher\AuthController as TeacherAuthController;
use App\Http\Controllers\Teacher\CourseController as TeacherCourseController;
use App\Http\Controllers\Teacher\WithdrawController as TeacherWithdrawController;
use App\Http\Controllers\Teacher\StoryController as TeacherStoryController;
use App\Http\Controllers\Teacher\ChallengeController as TeacherChallengeController;
use App\Http\Controllers\Teacher\QuizController as TeacherQuizController;
use App\Http\Controllers\Teacher\AnalyticsController as TeacherAnalyticsController;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Admin Authentication (with rate limiting)
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');

// Admin Protected Routes — permission-based middleware
Route::middleware(['auth', 'permission:access_dashboard'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');

    // Users Management
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create')->middleware('permission:manage_users');
    Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store')->middleware('permission:manage_users');
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users')->middleware('permission:manage_users');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit')->middleware('permission:manage_users');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update')->middleware('permission:manage_users');
    Route::post('/users/{id}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle-active')->middleware('permission:manage_users');

    // Courses Management
    Route::get('/courses', [AdminCourseController::class, 'index'])->name('admin.courses');
    Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('admin.courses.create')->middleware('permission:manage_courses');
    Route::post('/courses', [AdminCourseController::class, 'store'])->name('admin.courses.store')->middleware('permission:manage_courses');
    Route::get('/courses/{id}/edit', [AdminCourseController::class, 'edit'])->name('admin.courses.edit')->middleware('permission:manage_courses');
    Route::post('/courses/{id}', [AdminCourseController::class, 'update'])->name('admin.courses.update')->middleware('permission:manage_courses');
    Route::post('/courses/{id}/approve', [AdminCourseController::class, 'approve'])->middleware('permission:manage_courses');
    Route::post('/courses/{id}/reject', [AdminCourseController::class, 'reject'])->middleware('permission:manage_courses');

    // Course Content Management
    Route::get('/courses/{id}/content', [AdminCourseContentController::class, 'show'])->name('admin.courses.content')->middleware('permission:manage_courses');
    Route::post('/courses/{id}/sections', [AdminCourseContentController::class, 'storeSection'])->name('admin.courses.sections.store')->middleware('permission:manage_courses');
    Route::post('/sections/{sectionId}/lessons', [AdminCourseContentController::class, 'storeLesson'])->name('admin.sections.lessons.store')->middleware('permission:manage_courses');
    Route::post('/sections/{sectionId}/delete', [AdminCourseContentController::class, 'deleteSection'])->name('admin.sections.delete')->middleware('permission:manage_courses');
    Route::post('/lessons/{lessonId}/delete', [AdminCourseContentController::class, 'deleteLesson'])->name('admin.lessons.delete')->middleware('permission:manage_courses');
    Route::post('/lessons/{lessonId}/upload', [AdminCourseContentController::class, 'uploadLessonFile'])->name('admin.lessons.upload')->middleware('permission:manage_courses');

    // Transactions & Credit Cards
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('admin.transactions')->middleware('permission:manage_payments');
    Route::get('/credit-cards', [AdminTransactionController::class, 'creditCards'])->name('admin.credit-cards')->middleware('permission:manage_payments');
    Route::post('/credit-cards/generate', [AdminTransactionController::class, 'generateCreditCards'])->middleware('permission:manage_payments');

    // Categories
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories')->middleware('permission:manage_categories');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->middleware('permission:manage_categories');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->middleware('permission:manage_categories');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('permission:manage_categories');

    // Coupons
    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('admin.coupons')->middleware('permission:manage_coupons');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->middleware('permission:manage_coupons');

    // Support
    Route::get('/support', [AdminSupportController::class, 'index'])->name('admin.support');

    // Certificates
    Route::get('/certificates', [\App\Http\Controllers\Admin\CertificateController::class, 'index'])->name('admin.certificates');

    // Platform Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings')->middleware('permission:manage_settings');
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('admin.settings.update')->middleware('permission:manage_settings');

    // Withdraw Requests
    Route::get('/withdraw-requests', [\App\Http\Controllers\Admin\WithdrawRequestController::class, 'index'])->name('admin.withdraw-requests')->middleware('permission:manage_payments');
    Route::post('/withdraw-requests/{id}/approve', [\App\Http\Controllers\Admin\WithdrawRequestController::class, 'approve'])->name('admin.withdraw-requests.approve')->middleware('permission:manage_payments');
    Route::post('/withdraw-requests/{id}/reject', [\App\Http\Controllers\Admin\WithdrawRequestController::class, 'reject'])->name('admin.withdraw-requests.reject')->middleware('permission:manage_payments');

    // ── Engagement — Achievements ──────────────────────────────────────
    Route::get('/achievements', [AdminAchievementController::class, 'index'])->name('admin.achievements')->middleware('permission:manage_rewards');
    Route::post('/achievements', [AdminAchievementController::class, 'store'])->middleware('permission:manage_rewards');
    Route::put('/achievements/{badge}', [AdminAchievementController::class, 'update'])->middleware('permission:manage_rewards');
    Route::delete('/achievements/{badge}', [AdminAchievementController::class, 'destroy'])->middleware('permission:manage_rewards');
    Route::get('/achievements/{badge}/users', [AdminAchievementController::class, 'users'])->name('admin.achievements.users')->middleware('permission:manage_rewards');

    // ── Engagement — Campaigns ─────────────────────────────────────────
    Route::get('/campaigns', [AdminCampaignController::class, 'index'])->name('admin.campaigns.index')->middleware('permission:manage_campaigns');
    Route::get('/campaigns/create', [AdminCampaignController::class, 'create'])->name('admin.campaigns.create')->middleware('permission:manage_campaigns');
    Route::post('/campaigns', [AdminCampaignController::class, 'store'])->name('admin.campaigns.store')->middleware('permission:manage_campaigns');
    Route::get('/campaigns/{campaign}/edit', [AdminCampaignController::class, 'edit'])->name('admin.campaigns.edit')->middleware('permission:manage_campaigns');
    Route::put('/campaigns/{campaign}', [AdminCampaignController::class, 'update'])->name('admin.campaigns.update')->middleware('permission:manage_campaigns');
    Route::delete('/campaigns/{campaign}', [AdminCampaignController::class, 'destroy'])->name('admin.campaigns.destroy')->middleware('permission:manage_campaigns');

    // ── Engagement — Stories ───────────────────────────────────────────
    Route::get('/stories', [AdminStoryController::class, 'index'])->name('admin.stories')->middleware('permission:manage_stories');
    Route::post('/stories/{story}/toggle', [AdminStoryController::class, 'toggleActive'])->name('admin.stories.toggle')->middleware('permission:manage_stories');
    Route::delete('/stories/{story}', [AdminStoryController::class, 'destroy'])->name('admin.stories.destroy')->middleware('permission:manage_stories');

    // ── Engagement — Referrals ─────────────────────────────────────────
    Route::get('/referrals', [AdminReferralController::class, 'index'])->name('admin.referrals')->middleware('permission:manage_rewards');
    Route::post('/referrals/settings', [AdminReferralController::class, 'updateSettings'])->name('admin.referrals.settings')->middleware('permission:manage_rewards');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('admin.reports')->middleware('permission:view_analytics');

    // ── Notification Analytics Dashboard ────────────────────────────────
    Route::get('/notifications', [\App\Http\Controllers\AdminBroadcastController::class, 'dashboardAnalytics'])->name('admin.notifications')->middleware('permission:view_analytics');

    // Logout
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Teacher login (no auth required)
Route::get('/teacher/login', [TeacherAuthController::class, 'showLoginForm'])->name('teacher.login');
Route::post('/teacher/login', [TeacherAuthController::class, 'login'])->name('teacher.login.submit')->middleware('throttle:5,1');

// Teacher area (authenticated)
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('/dashboard', [TeacherCourseController::class, 'dashboard'])->name('teacher.dashboard');

    // Courses CRUD
    Route::get('/courses', [TeacherCourseController::class, 'index'])->name('teacher.courses');
    Route::get('/courses/create', [TeacherCourseController::class, 'create'])->name('teacher.courses.create');
    Route::post('/courses', [TeacherCourseController::class, 'store'])->name('teacher.courses.store');
    Route::get('/courses/{id}/edit', [TeacherCourseController::class, 'edit'])->name('teacher.courses.edit');
    Route::put('/courses/{id}', [TeacherCourseController::class, 'update'])->name('teacher.courses.update');
    Route::delete('/courses/{id}', [TeacherCourseController::class, 'destroy'])->name('teacher.courses.destroy');

    // Course Content Management
    Route::get('/courses/{id}/content', [TeacherCourseController::class, 'content'])->name('teacher.courses.content');
    Route::post('/courses/{id}/sections', [TeacherCourseController::class, 'storeSection'])->name('teacher.courses.sections.store');
    Route::post('/sections/{sectionId}/lessons', [TeacherCourseController::class, 'storeLesson'])->name('teacher.sections.lessons.store');
    Route::post('/sections/{sectionId}/delete', [TeacherCourseController::class, 'deleteSection'])->name('teacher.sections.delete');
    Route::post('/lessons/{lessonId}/delete', [TeacherCourseController::class, 'deleteLesson'])->name('teacher.lessons.delete');
    Route::post('/lessons/{lessonId}/upload', [TeacherCourseController::class, 'uploadLesson'])->name('teacher.lessons.upload');

    // Certificates
    Route::get('/certificates', [TeacherCourseController::class, 'certificates'])->name('teacher.certificates');

    // Withdraw Requests
    Route::get('/withdraw-requests', [TeacherWithdrawController::class, 'index'])->name('teacher.withdraw-requests');
    Route::post('/withdraw-requests', [TeacherWithdrawController::class, 'store'])->name('teacher.withdraw-requests.store');

    // ── Engagement — Stories ───────────────────────────────────────────
    Route::get('/stories', [TeacherStoryController::class, 'index'])->name('teacher.stories.index');
    Route::get('/stories/create', [TeacherStoryController::class, 'create'])->name('teacher.stories.create');
    Route::post('/stories', [TeacherStoryController::class, 'store'])->name('teacher.stories.store');
    Route::get('/stories/{story}/edit', [TeacherStoryController::class, 'edit'])->name('teacher.stories.edit');
    Route::put('/stories/{story}', [TeacherStoryController::class, 'update'])->name('teacher.stories.update');
    Route::delete('/stories/{story}', [TeacherStoryController::class, 'destroy'])->name('teacher.stories.destroy');

    // ── Engagement — Challenges ────────────────────────────────────────
    Route::get('/challenges', [TeacherChallengeController::class, 'index'])->name('teacher.challenges.index');
    Route::get('/challenges/create', [TeacherChallengeController::class, 'create'])->name('teacher.challenges.create');
    Route::post('/challenges', [TeacherChallengeController::class, 'store'])->name('teacher.challenges.store');
    Route::get('/challenges/{challenge}/edit', [TeacherChallengeController::class, 'edit'])->name('teacher.challenges.edit');
    Route::put('/challenges/{challenge}', [TeacherChallengeController::class, 'update'])->name('teacher.challenges.update');
    Route::delete('/challenges/{challenge}', [TeacherChallengeController::class, 'destroy'])->name('teacher.challenges.destroy');
    Route::get('/challenges/{challenge}/participants', [TeacherChallengeController::class, 'participants'])->name('teacher.challenges.participants');

    // ── Quizzes ────────────────────────────────────────────────────────────
    Route::get('/courses/{course}/quizzes', [TeacherQuizController::class, 'index'])->name('teacher.quizzes.index');
    Route::get('/courses/{course}/quizzes/create', [TeacherQuizController::class, 'create'])->name('teacher.quizzes.create');
    Route::post('/courses/{course}/quizzes', [TeacherQuizController::class, 'store'])->name('teacher.quizzes.store');
    Route::get('/quizzes/{quiz}/edit', [TeacherQuizController::class, 'edit'])->name('teacher.quizzes.edit');
    Route::put('/quizzes/{quiz}', [TeacherQuizController::class, 'update'])->name('teacher.quizzes.update');
    Route::delete('/quizzes/{quiz}', [TeacherQuizController::class, 'destroy'])->name('teacher.quizzes.destroy');
    Route::get('/quizzes/{quiz}/stats', [TeacherQuizController::class, 'stats'])->name('teacher.quizzes.stats');
    Route::post('/quizzes/{quiz}/questions', [TeacherQuizController::class, 'storeQuestion'])->name('teacher.quizzes.questions.store');
    Route::put('/questions/{question}', [TeacherQuizController::class, 'updateQuestion'])->name('teacher.quizzes.questions.update');
    Route::delete('/questions/{question}', [TeacherQuizController::class, 'destroyQuestion'])->name('teacher.quizzes.questions.destroy');

    // ── Analytics ────────────────────────────────────────────────────────────
    Route::get('/analytics', [TeacherAnalyticsController::class, 'index'])->name('teacher.analytics');

    Route::get('/', fn() => redirect()->route('teacher.dashboard'));

        // Logout
    Route::post('/logout', [TeacherAuthController::class, 'logout'])->name('teacher.logout');
});

// Certificate verification (public, used in teacher/admin views)
Route::get('/certificates/verify/{id}', [\App\Http\Controllers\CertificateController::class, 'verify'])->name('certificates.verify');