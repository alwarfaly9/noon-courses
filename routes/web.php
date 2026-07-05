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
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Teacher\AuthController as TeacherAuthController;
use App\Http\Controllers\Teacher\CourseController as TeacherCourseController;
use App\Http\Controllers\Teacher\WithdrawController as TeacherWithdrawController;

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
    Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('admin.courses.create');
    Route::post('/courses', [AdminCourseController::class, 'store'])->name('admin.courses.store');
    Route::get('/courses/{id}/edit', [AdminCourseController::class, 'edit'])->name('admin.courses.edit');
    Route::post('/courses/{id}', [AdminCourseController::class, 'update'])->name('admin.courses.update');
    Route::post('/courses/{id}/approve', [AdminCourseController::class, 'approve'])->middleware('permission:manage_courses');
    Route::post('/courses/{id}/reject', [AdminCourseController::class, 'reject'])->middleware('permission:manage_courses');

    // Course Content Management
    Route::get('/courses/{id}/content', [AdminCourseContentController::class, 'show'])->name('admin.courses.content');
    Route::post('/courses/{id}/sections', [AdminCourseContentController::class, 'storeSection'])->name('admin.courses.sections.store');
    Route::post('/sections/{sectionId}/lessons', [AdminCourseContentController::class, 'storeLesson'])->name('admin.sections.lessons.store');
    Route::post('/sections/{sectionId}/delete', [AdminCourseContentController::class, 'deleteSection'])->name('admin.sections.delete');
    Route::post('/lessons/{lessonId}/delete', [AdminCourseContentController::class, 'deleteLesson'])->name('admin.lessons.delete');
    Route::post('/lessons/{lessonId}/upload', [AdminCourseContentController::class, 'uploadLessonFile'])->name('admin.lessons.upload');

    // Transactions & Credit Cards
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('admin.transactions')->middleware('permission:manage_payments');
    Route::get('/credit-cards', [AdminTransactionController::class, 'creditCards'])->name('admin.credit-cards')->middleware('permission:manage_payments');
    Route::post('/credit-cards/generate', [AdminTransactionController::class, 'generateCreditCards'])->middleware('permission:manage_payments');

    // Categories
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->middleware('permission:manage_categories');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->middleware('permission:manage_categories');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('permission:manage_categories');

    // Coupons
    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('admin.coupons');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->middleware('permission:manage_coupons');

    // Support
    Route::get('/support', [AdminSupportController::class, 'index'])->name('admin.support');

    // Certificates
    Route::get('/certificates', [\App\Http\Controllers\Admin\CertificateController::class, 'index'])->name('admin.certificates');

    // Platform Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('admin.settings.update');

    // Withdraw Requests
    Route::get('/withdraw-requests', [\App\Http\Controllers\Admin\WithdrawRequestController::class, 'index'])->name('admin.withdraw-requests')->middleware('permission:manage_payments');
    Route::post('/withdraw-requests/{id}/approve', [\App\Http\Controllers\Admin\WithdrawRequestController::class, 'approve'])->name('admin.withdraw-requests.approve')->middleware('permission:manage_payments');
    Route::post('/withdraw-requests/{id}/reject', [\App\Http\Controllers\Admin\WithdrawRequestController::class, 'reject'])->name('admin.withdraw-requests.reject')->middleware('permission:manage_payments');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('admin.reports');

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

    // Withdraw Requests
    Route::get('/withdraw-requests', [TeacherWithdrawController::class, 'index'])->name('teacher.withdraw-requests');
    Route::post('/withdraw-requests', [TeacherWithdrawController::class, 'store'])->name('teacher.withdraw-requests.store');

    Route::get('/', fn() => redirect()->route('teacher.dashboard'));

    // Logout
    Route::post('/logout', [TeacherAuthController::class, 'logout'])->name('teacher.logout');
});