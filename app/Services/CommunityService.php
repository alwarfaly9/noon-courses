<?php

namespace App\Services;

use App\Events\CommentCreated;
use App\Events\CommentReplied;
use App\Models\CommentReaction;
use App\Models\CourseLesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommunityService
{
    /**
     * Post a top-level comment or a reply on a lesson.
     *
     * @throws \Exception if lesson not found or user not enrolled
     */
    public function postComment(User $user, CourseLesson $lesson, string $content, ?int $parentId = null): LessonComment
    {
        // Verify enrollment (students must be enrolled; teachers/admins are exempt)
        if (!$user->hasRole('admin') && !$user->hasRole('teacher')) {
            $enrolled = DB::table('course_enrollments')
                ->where('student_id', $user->id)
                ->where('course_id', $lesson->course_id)
                ->exists();

            if (!$enrolled) {
                throw new \Exception('يجب التسجيل في الدورة للتعليق');
            }
        }

        if ($parentId) {
            $parent = LessonComment::where('id', $parentId)
                ->where('lesson_id', $lesson->id)
                ->whereNull('parent_id') // Only one reply level deep
                ->firstOrFail();
        }

        $comment = LessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id'   => $user->id,
            'parent_id' => $parentId,
            'content'   => $content,
        ]);

        // Dispatch comment event
        CommentCreated::dispatch($comment);

        // Increment replies counter on parent
        if ($parentId) {
            LessonComment::where('id', $parentId)->increment('replies_count');

            // Dispatch reply notification event
            $parentComment = LessonComment::find($parentId);
            if ($parentComment && $parentComment->user_id !== $user->id) {
                CommentReplied::dispatch($comment, $parentComment);
            }
        }

        // First-comment XP reward
        $totalComments = LessonComment::where('user_id', $user->id)->count();
        if ($totalComments === 1) {
            app(GamificationService::class)->onFirstComment($user);
        }

        // Milestone: 10 comments → engagement badge check
        if (in_array($totalComments, [10, 25, 50, 100])) {
            \App\Models\UserStats::where('user_id', $user->id)
                ->update(['comments_posted' => $totalComments]);
            app(GamificationService::class)->checkAndAwardBadges($user);
        }

        return $comment->load('user:id,name,avatar');
    }

    /**
     * Toggle like on a comment. Returns new like count.
     */
    public function toggleLike(User $user, LessonComment $comment): array
    {
        $existing = CommentReaction::where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $comment->decrement('likes_count');
            return ['liked' => false, 'likes_count' => $comment->likes_count - 1];
        }

        CommentReaction::create([
            'comment_id' => $comment->id,
            'user_id'    => $user->id,
            'type'       => 'like',
        ]);
        $comment->increment('likes_count');
        $newLikes = $comment->likes_count + 1;

        // Award the comment AUTHOR for receiving recognition milestones (5, 20, 50 likes)
        if (in_array($newLikes, [5, 20, 50])) {
            $author = \App\Models\User::find($comment->user_id);
            if ($author) {
                $xp = match ($newLikes) { 5 => 10, 20 => 25, 50 => 50, default => 0 };
                app(GamificationService::class)->awardXp($author, $xp, "comment_liked_{$newLikes}");
                \App\Models\UserStats::where('user_id', $author->id)->increment('helpful_votes_received', 1);
            }
        }

        return ['liked' => true, 'likes_count' => $newLikes];
    }

    /**
     * Soft-delete a comment (owner or admin only).
     *
     * @throws \Exception
     */
    public function deleteComment(User $user, LessonComment $comment): void
    {
        if ($comment->user_id !== $user->id && !$user->hasRole('admin')) {
            throw new \Exception('غير مصرح لك بحذف هذا التعليق');
        }

        $comment->delete();

        // Decrement parent replies counter
        if ($comment->parent_id) {
            LessonComment::where('id', $comment->parent_id)->decrement('replies_count');
        }
    }

    /**
     * Report a comment. Auto-hide if reported_count exceeds threshold.
     */
    public function reportComment(LessonComment $comment): void
    {
        $comment->increment('reported_count');

        // Auto-hide after 5 reports
        if ($comment->reported_count >= 5) {
            $comment->forceFill(['is_approved' => false])->save();
        }
    }
}
