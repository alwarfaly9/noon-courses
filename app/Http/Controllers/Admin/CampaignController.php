<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Badge;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::withCount('participations')
            ->latest()
            ->paginate(20);

        return view('admin.campaigns', compact('campaigns'));
    }

    public function create()
    {
        $badges = Badge::select('id', 'name')->get();
        return view('admin.campaign-form', compact('badges'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'slug'             => 'required|string|unique:campaigns,slug',
            'type'             => 'required|in:weekly_challenge,monthly,seasonal',
            'description'      => 'nullable|string',
            'banner_image_url' => 'nullable|url',
            'reward_xp'        => 'integer|min:0',
            'reward_badge_id'  => 'nullable|exists:badges,id',
            'goal_type'        => 'required|string|max:100',
            'goal_value'       => 'required|integer|min:1',
            'is_active'        => 'boolean',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date|after:starts_at',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Campaign::create($data);

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'تم إنشاء الحملة بنجاح');
    }

    public function edit(Campaign $campaign)
    {
        $badges = Badge::select('id', 'name')->get();
        return view('admin.campaign-form', compact('campaign', 'badges'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'name'             => 'string|max:255',
            'type'             => 'in:weekly_challenge,monthly,seasonal',
            'description'      => 'nullable|string',
            'banner_image_url' => 'nullable|url',
            'reward_xp'        => 'integer|min:0',
            'reward_badge_id'  => 'nullable|exists:badges,id',
            'goal_type'        => 'string|max:100',
            'goal_value'       => 'integer|min:1',
            'is_active'        => 'boolean',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date',
        ]);

        $campaign->update($data);

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'تم تحديث الحملة بنجاح');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return back()->with('success', 'تم حذف الحملة');
    }
}
