<?php

namespace App\Http\Controllers;

use App\Models\CourseReview;
use App\Models\ReviewHelpfulVote;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * PUT /reviews/{review}
     * Edit own review (must be owner, within 30-day edit window).
     */
    public function update(Request $request, CourseReview $review)
    {
        $user = $request->user();

        if ($review->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // 30-day edit window from the original submission
        if ($review->created_at->diffInDays(now()) > 30) {
            return response()->json([
                'success' => false,
                'message' => 'Reviews can only be edited within 30 days of submission',
            ], 422);
        }

        $data = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'review' => 'sometimes|string|max:2000',
        ]);

        $review->update($data);

        // Recalculate course average rating
        $course = $review->course;
        $course->update([
            'average_rating' => $course->reviews()->where('is_approved', true)->avg('rating'),
        ]);

        return response()->json(['success' => true, 'data' => $review->fresh()]);
    }

    /**
     * DELETE /reviews/{review}
     * Delete own review.
     */
    public function destroy(Request $request, CourseReview $review)
    {
        $user = $request->user();

        if ($review->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $course = $review->course;
        $review->delete();

        $newAvg = $course->reviews()->where('is_approved', true)->avg('rating');
        $course->update(['average_rating' => $newAvg ?? 0]);

        return response()->json(['success' => true, 'message' => 'Review deleted']);
    }

    /**
     * POST /reviews/{review}/helpful
     * Toggle helpful vote on a review.
     */
    public function toggleHelpful(Request $request, CourseReview $review)
    {
        $userId = $request->user()->id;

        $existing = ReviewHelpfulVote::where('review_id', $review->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $review->decrement('helpful_votes');
            $voted = false;
        } else {
            ReviewHelpfulVote::create(['review_id' => $review->id, 'user_id' => $userId]);
            $review->increment('helpful_votes');
            $voted = true;
        }

        return response()->json([
            'success'       => true,
            'voted'         => $voted,
            'helpful_votes' => $review->fresh()->helpful_votes,
        ]);
    }

    /**
     * POST /admin/reviews/{review}/feature
     * Admin: toggle featured status of a review.
     */
    public function feature(Request $request, CourseReview $review)
    {
        $review->forceFill(['is_featured' => !$review->is_featured])->save();

        return response()->json([
            'success'     => true,
            'is_featured' => $review->is_featured,
        ]);
    }
}
