<?php

namespace App\Http\Controllers;

use App\Models\PromotionalBanner;
use App\Services\CacheService;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /** GET /banners — Public list of active banners */
    public function index()
    {
        $banners = CacheService::remember(
            CacheService::bannersKey(),
            CacheService::TTL_LONG,
            fn() => PromotionalBanner::visible()->get()
        );

        return response()->json(['success' => true, 'data' => $banners]);
    }

    // ── Admin CRUD ─────────────────────────────────────────────────────────────

    public function adminIndex()
    {
        return response()->json([
            'success' => true,
            'data'    => PromotionalBanner::orderBy('sort_order')->paginate(20),
        ]);
    }

    public function adminStore(Request $request)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'subtitle'         => 'nullable|string|max:500',
            'image_url'        => 'nullable|url',
            'action_url'       => 'nullable|string|max:500',
            'action_label'     => 'nullable|string|max:100',
            'background_color' => 'nullable|string|max:7',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer|min:0',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date|after:starts_at',
        ]);

        $banner = PromotionalBanner::create($data);

        return response()->json(['success' => true, 'data' => $banner], 201);
    }

    public function adminUpdate(Request $request, PromotionalBanner $banner)
    {
        $data = $request->validate([
            'title'            => 'string|max:255',
            'subtitle'         => 'nullable|string|max:500',
            'image_url'        => 'nullable|url',
            'action_url'       => 'nullable|string|max:500',
            'action_label'     => 'nullable|string|max:100',
            'background_color' => 'nullable|string|max:7',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer|min:0',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date',
        ]);

        $banner->update($data);
        CacheService::invalidateStatic();

        return response()->json(['success' => true, 'data' => $banner->fresh()]);
    }

    public function adminDestroy(PromotionalBanner $banner)
    {
        $banner->delete();
        CacheService::invalidateStatic();

        return response()->json(['success' => true, 'message' => 'Banner deleted']);
    }
}
