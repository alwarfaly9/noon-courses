<?php

namespace App\Http\Controllers;

use App\Models\CourseLesson;
use App\Models\LessonComment;
use App\Services\CommunityService;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function __construct(private readonly CommunityService $service) {}

    // ── Comments ─────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/lessons/{lessonId}/comments
     */
    public function index(Request $request, int $lessonId)
    {
        $comments = LessonComment::where('lesson_id', $lessonId)
            ->approved()
            ->topLevel()
            ->with([
                'user:id,name,avatar',
                'replies' => fn($q) => $q->with('user:id,name,avatar')->approved(),
            ])
            ->withCount('reactions as likes_count')
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate(20);

        // Annotate whether the authenticated user liked each comment
        if ($request->user()) {
            $userId     = $request->user()->id;
            $commentIds = $comments->pluck('id');
            $liked = \App\Models\CommentReaction::where('user_id', $userId)
                ->whereIn('comment_id', $commentIds)
                ->pluck('comment_id')
                ->flip();

            $comments->getCollection()->transform(function ($c) use ($liked) {
                $c->user_liked = isset($liked[$c->id]);
                return $c;
            });
        }

        return response()->json(['success' => true, 'data' => $comments]);
    }

    /**
     * POST /api/v1/lessons/{lessonId}/comments
     */
    public function store(Request $request, int $lessonId)
    {
        $request->validate([
            'content'   => 'required|string|max:2000',
            'parent_id' => 'nullable|integer|exists:lesson_comments,id',
        ]);

        $lesson = CourseLesson::findOrFail($lessonId);

        try {
            $comment = $this->service->postComment(
                $request->user(),
                $lesson,
                $request->content,
                $request->parent_id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم نشر التعليق',
                'data'    => $comment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * DELETE /api/v1/comments/{id}
     */
    public function destroy(Request $request, LessonComment $comment)
    {
        try {
            $this->service->deleteComment($request->user(), $comment);

            return response()->json(['success' => true, 'message' => 'تم حذف التعليق']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * POST /api/v1/comments/{id}/like   (toggle)
     */
    public function like(Request $request, LessonComment $comment)
    {
        $result = $this->service->toggleLike($request->user(), $comment);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /api/v1/comments/{id}/report
     */
    public function report(Request $request, LessonComment $comment)
    {
        $this->service->reportComment($comment);

        return response()->json(['success' => true, 'message' => 'تم إرسال البلاغ. سيتم مراجعته.']);
    }

    // ── Admin Moderation ──────────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/comments   — pending moderation queue
     */
    public function adminIndex(Request $request)
    {
        $comments = LessonComment::where(function ($q) {
                $q->where('is_approved', false)
                  ->orWhere('reported_count', '>=', 3);
            })
            ->with('user:id,name', 'lesson:id,title')
            ->latest()
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $comments]);
    }

    /**
     * POST /api/v1/admin/comments/{id}/approve
     */
    public function approve(LessonComment $comment)
    {
        $comment->forceFill(['is_approved' => true, 'reported_count' => 0])->save();

        return response()->json(['success' => true, 'message' => 'تمت الموافقة على التعليق']);
    }

    /**
     * DELETE /api/v1/admin/comments/{id}
     */
    public function adminDestroy(LessonComment $comment)
    {
        $comment->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف التعليق']);
    }
}
