<?php

namespace App\Http\Controllers;

use App\Models\SuccessStory;
use App\Services\CacheService;
use Illuminate\Http\Request;

class SuccessStoryController extends Controller
{
    /** GET /success-stories — Public approved stories */
    public function index(Request $request)
    {
        $featured = $request->boolean('featured');
        $page     = (int) $request->input('page', 1);
        $cacheKey = CacheService::storiesKey($page, $featured);

        $stories = CacheService::remember($cacheKey, CacheService::TTL_MEDIUM, function () use ($featured) {
            return SuccessStory::published()
                ->with('user:id,name,avatar')
                ->when($featured, fn($q) => $q->where('is_featured', true))
                ->latest()
                ->paginate(12);
        });

        return response()->json(['success' => true, 'data' => $stories]);
    }

    /** POST /success-stories — Student submits their story */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'              => 'required|string|max:255',
            'body'               => 'required|string|max:5000',
            'before_description' => 'nullable|string|max:1000',
            'after_description'  => 'nullable|string|max:1000',
            'image_url'          => 'nullable|url',
        ]);

        $story = (new SuccessStory())->forceFill(array_merge($data, [
            'user_id'     => $request->user()->id,
            'is_approved' => false,
            'is_featured' => false,
        ]));
        $story->save();

        return response()->json([
            'success' => true,
            'message' => 'قصتك قيد المراجعة. شكراً لمشاركتنا تجربتك!',
            'data'    => $story,
        ], 201);
    }

    // ── Admin ──────────────────────────────────────────────────────────────────

    public function adminIndex()
    {
        return response()->json([
            'success' => true,
            'data'    => SuccessStory::with('user:id,name,avatar')->latest()->paginate(20),
        ]);
    }

    public function adminApprove(SuccessStory $story)
    {
        $story->forceFill(['is_approved' => !$story->is_approved])->save();
        CacheService::invalidateStories();

        return response()->json(['success' => true, 'is_approved' => $story->is_approved]);
    }

    public function adminFeature(SuccessStory $story)
    {
        $story->forceFill(['is_featured' => !$story->is_featured])->save();
        CacheService::invalidateStories();

        return response()->json(['success' => true, 'is_featured' => $story->is_featured]);
    }

    public function adminDestroy(SuccessStory $story)
    {
        $story->delete();
        CacheService::invalidateStories();

        return response()->json(['success' => true, 'message' => 'Story deleted']);
    }
}
