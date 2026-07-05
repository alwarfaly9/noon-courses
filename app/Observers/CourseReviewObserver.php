<?php

namespace App\Observers;

use App\Models\CourseReview;

class CourseReviewObserver
{
    private function updateCourseRating(CourseReview $courseReview): void
    {
        $course = $courseReview->course;
        if ($course) {
            $avgRating = \App\Models\CourseReview::where('course_id', $course->id)
                ->where('is_approved', true) // Assuming only approved count? Or all? Let's say all for now as in CourseController
                ->avg('rating');
            
            $reviewsCount = \App\Models\CourseReview::where('course_id', $course->id)->count();
            
            $course->update([
                'rating' => round((float)$avgRating, 2),
                'reviews_count' => $reviewsCount,
            ]);
        }
    }

    /**
     * Handle the CourseReview "created" event.
     */
    public function created(CourseReview $courseReview): void
    {
        $this->updateCourseRating($courseReview);
    }

    /**
     * Handle the CourseReview "updated" event.
     */
    public function updated(CourseReview $courseReview): void
    {
        if ($courseReview->wasChanged('rating') || $courseReview->wasChanged('is_approved')) {
            $this->updateCourseRating($courseReview);
        }
    }

    /**
     * Handle the CourseReview "deleted" event.
     */
    public function deleted(CourseReview $courseReview): void
    {
        $this->updateCourseRating($courseReview);
    }

    /**
     * Handle the CourseReview "restored" event.
     */
    public function restored(CourseReview $courseReview): void
    {
        $this->updateCourseRating($courseReview);
    }

    /**
     * Handle the CourseReview "force deleted" event.
     */
    public function forceDeleted(CourseReview $courseReview): void
    {
        $this->updateCourseRating($courseReview);
    }
}
