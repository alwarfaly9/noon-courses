<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignParticipation;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /** GET /campaigns — Active campaigns with user participation */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // Eager-load participations to avoid N+1 (one extra query instead of N)
        $campaigns = Campaign::active()
            ->with(['participations' => fn($q) => $q->where('user_id', $userId)])
            ->get()
            ->map(function ($campaign) {
                $participation = $campaign->participations->first();
                $arr = $campaign->toArray();
                unset($arr['participations']);
                $arr['participation'] = $participation ? [
                    'progress'  => $participation->progress,
                    'completed' => $participation->completed,
                ] : null;
                return $arr;
            });

        return response()->json(['success' => true, 'data' => $campaigns]);
    }

    /** POST /campaigns/{campaign}/join */
    public function join(Request $request, Campaign $campaign)
    {
        if (!$campaign->is_active) {
            return response()->json(['success' => false, 'message' => 'Campaign is not active'], 422);
        }

        $existing = CampaignParticipation::where([
            'campaign_id' => $campaign->id,
            'user_id'     => $request->user()->id,
        ])->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Already participating in this campaign'], 422);
        }

        $participation = CampaignParticipation::create([
            'campaign_id' => $campaign->id,
            'user_id'     => $request->user()->id,
            'progress'    => 0,
            'completed'   => false,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $participation,
        ]);
    }

    // ── Admin CRUD ─────────────────────────────────────────────────────────────

    public function adminIndex()
    {
        return response()->json(['success' => true, 'data' => Campaign::latest()->paginate(20)]);
    }

    public function adminStore(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'slug'            => 'required|string|unique:campaigns,slug',
            'type'            => 'required|in:weekly_challenge,monthly,seasonal',
            'description'     => 'nullable|string',
            'banner_image_url' => 'nullable|url',
            'reward_xp'       => 'integer|min:0',
            'reward_badge_id' => 'nullable|exists:badges,id',
            'goal_type'       => 'required|string|max:100',
            'goal_value'      => 'required|integer|min:1',
            'is_active'       => 'boolean',
            'starts_at'       => 'nullable|date',
            'ends_at'         => 'nullable|date|after:starts_at',
        ]);

        $campaign = Campaign::create($data);

        return response()->json(['success' => true, 'data' => $campaign], 201);
    }

    public function adminUpdate(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'name'            => 'string|max:255',
            'type'            => 'in:weekly_challenge,monthly,seasonal',
            'description'     => 'nullable|string',
            'banner_image_url' => 'nullable|url',
            'reward_xp'       => 'integer|min:0',
            'reward_badge_id' => 'nullable|exists:badges,id',
            'goal_type'       => 'string|max:100',
            'goal_value'      => 'integer|min:1',
            'is_active'       => 'boolean',
            'starts_at'       => 'nullable|date',
            'ends_at'         => 'nullable|date',
        ]);

        $campaign->update($data);

        return response()->json(['success' => true, 'data' => $campaign->fresh()]);
    }

    public function adminDestroy(Campaign $campaign)
    {
        $campaign->delete();

        return response()->json(['success' => true, 'message' => 'Campaign deleted']);
    }
}
