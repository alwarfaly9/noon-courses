<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AchievementController extends Controller
{
    public function index()
    {
        $badges = Badge::latest()->paginate(20);
        return view('admin.achievements', compact('badges'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'slug'            => 'nullable|string|unique:badges,slug',
            'description'     => 'nullable|string',
            'icon'            => 'nullable|string',
            'type'            => 'required|in:lesson,course,streak,quiz,path,level,special',
            'condition_type'  => 'nullable|string',
            'condition_value' => 'required|integer|min:1',
            'xp_reward'       => 'integer|min:0',
            'is_active'       => 'boolean',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']) . '-' . substr(uniqid(), -4);
        $data['is_active'] = $request->boolean('is_active', true);

        Badge::create($data);

        return back()->with('success', 'تم إضافة الشارة بنجاح');
    }

    public function update(Request $request, Badge $badge)
    {
        $data = $request->validate([
            'name'            => 'string|max:255',
            'description'     => 'nullable|string',
            'icon'            => 'nullable|string',
            'type'            => 'in:lesson,course,streak,quiz,path,level,special',
            'condition_type'  => 'nullable|string',
            'condition_value' => 'integer|min:1',
            'xp_reward'       => 'integer|min:0',
            'is_active'       => 'boolean',
        ]);

        $badge->update($data);

        return back()->with('success', 'تم تحديث الشارة بنجاح');
    }

    public function destroy(Badge $badge)
    {
        $badge->delete();
        return back()->with('success', 'تم حذف الشارة');
    }

    public function users(Badge $badge)
    {
        $users = UserBadge::where('badge_id', $badge->id)
            ->with('user')
            ->latest('earned_at')
            ->paginate(20);

        return view('admin.achievement-users', compact('badge', 'users'));
    }
}
