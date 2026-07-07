<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Services\StoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    public function __construct(private StoryService $storyService) {}

    public function index()
    {
        $stories = Story::with('user:id,name', 'course:id,title')
            ->latest()
            ->paginate(20);

        return view('admin.stories', compact('stories'));
    }

    public function toggleActive(Story $story)
    {
        $story->update(['is_active' => !$story->is_active]);
        return back()->with('success', 'تم تحديث حالة القصة');
    }

    public function destroy(Story $story)
    {
        if ($story->media_path) {
            Storage::disk('public')->delete($story->media_path);
        }

        $story->delete();
        return back()->with('success', 'تم حذف القصة');
    }

    public function stats(Story $story)
    {
        $stats = $this->storyService->getViewStats($story);
        return response()->json(['success' => true, 'data' => $stats]);
    }
}
