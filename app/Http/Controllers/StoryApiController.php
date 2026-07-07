<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Services\StoryService;
use Illuminate\Http\Request;

class StoryApiController extends Controller
{
    public function __construct(private StoryService $storyService) {}

    public function index(Request $request)
    {
        $courseId = $request->query('course_id');
        $stories = $this->storyService->getActiveStories($courseId);

        return response()->json(['success' => true, 'data' => $stories]);
    }

    public function recordView(Request $request, Story $story)
    {
        $story->loadExists(['views as already_viewed' => fn($q) => $q->where('user_id', $request->user()->id)]);

        if (!$story->is_active || ($story->expires_at && $story->expires_at->isPast())) {
            return response()->json(['success' => false, 'message' => 'القصة غير متاحة'], 410);
        }

        $viewed = $this->storyService->recordView($story, $request->user());

        return response()->json([
            'success' => true,
            'message' => $viewed ? 'تم تسجيل المشاهدة' : 'سبق المشاهدة',
            'views_count' => $story->views_count,
        ]);
    }
}
