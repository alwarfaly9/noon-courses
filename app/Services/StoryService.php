<?php

namespace App\Services;

use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoryService
{
    public function createStory(User $user, array $data): Story
    {
        $data['user_id'] = $user->id;

        if (isset($data['media']) && $data['media'] instanceof UploadedFile) {
            $path = $data['media']->store('stories/' . $user->id, 'public');
            $data['media_path'] = $path;
            $data['media_type'] = str_starts_with($data['media']->getMimeType(), 'video/') ? 'video' : 'image';
            unset($data['media']);
        }

        $data['is_active'] = $data['is_active'] ?? true;
        $data['views_count'] = 0;

        return Story::create($data);
    }

    public function updateStory(Story $story, array $data): Story
    {
        if (isset($data['media']) && $data['media'] instanceof UploadedFile) {
            if ($story->media_path) {
                Storage::disk('public')->delete($story->media_path);
            }
            $path = $data['media']->store('stories/' . $story->user_id, 'public');
            $data['media_path'] = $path;
            $data['media_type'] = str_starts_with($data['media']->getMimeType(), 'video/') ? 'video' : 'image';
            unset($data['media']);
        }

        $story->update($data);
        return $story->fresh();
    }

    public function recordView(Story $story, User $user): bool
    {
        $view = StoryView::firstOrCreate(
            ['story_id' => $story->id, 'user_id' => $user->id],
            ['viewed_at' => now()],
        );

        if (!$view->wasRecentlyCreated) {
            return false;
        }

        $story->increment('views_count');

        return true;
    }

    public function getActiveStories(?int $courseId = null): mixed
    {
        $query = Story::active()
            ->with('user:id,name,avatar')
            ->latest();

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        return $query->get();
    }

    public function getTeacherStories(User $teacher): mixed
    {
        return Story::where('user_id', $teacher->id)
            ->latest()
            ->paginate(20);
    }

    public function getViewStats(Story $story): array
    {
        $totalViews = $story->views_count;
        $uniqueViewers = StoryView::where('story_id', $story->id)->count();

        return [
            'total_views'     => $totalViews,
            'unique_viewers'  => $uniqueViewers,
            'viewed_at'       => $story->views()->latest('viewed_at')->first()?->viewed_at,
        ];
    }
}
