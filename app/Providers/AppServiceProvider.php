<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');

        \App\Models\CourseReview::observe(\App\Observers\CourseReviewObserver::class);
        \App\Models\LearningPathEnrollment::observe(\App\Observers\LearningPathEnrollmentObserver::class);
    }
}
