<?php

namespace App\Observers;

use App\Models\LearningPathEnrollment;

class LearningPathEnrollmentObserver
{
    private function updateLearningPathStats(LearningPathEnrollment $learningPathEnrollment): void
    {
        $learningPath = $learningPathEnrollment->learningPath;
        if ($learningPath) {
            $learningPath->update([
                'enrollments_count' => \App\Models\LearningPathEnrollment::where('learning_path_id', $learningPath->id)->count(),
            ]);
        }
    }

    /**
     * Handle the LearningPathEnrollment "created" event.
     */
    public function created(LearningPathEnrollment $learningPathEnrollment): void
    {
        $this->updateLearningPathStats($learningPathEnrollment);
    }

    /**
     * Handle the LearningPathEnrollment "updated" event.
     */
    public function updated(LearningPathEnrollment $learningPathEnrollment): void
    {
        //
    }

    /**
     * Handle the LearningPathEnrollment "deleted" event.
     */
    public function deleted(LearningPathEnrollment $learningPathEnrollment): void
    {
        $this->updateLearningPathStats($learningPathEnrollment);
    }

    /**
     * Handle the LearningPathEnrollment "restored" event.
     */
    public function restored(LearningPathEnrollment $learningPathEnrollment): void
    {
        $this->updateLearningPathStats($learningPathEnrollment);
    }

    /**
     * Handle the LearningPathEnrollment "force deleted" event.
     */
    public function forceDeleted(LearningPathEnrollment $learningPathEnrollment): void
    {
        $this->updateLearningPathStats($learningPathEnrollment);
    }
}
