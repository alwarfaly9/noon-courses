<?php

namespace App\Http\Controllers;

use App\Events\AnnouncementCreated;
use App\Models\Notification;
use App\Models\NotificationAnalytics;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class AdminBroadcastController extends Controller
{
    /**
     * Send a broadcast notification to users.
     * Rate-limited to 1 broadcast per 5 minutes per admin.
     */
    public function sendBroadcast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|in:all,students,teachers,system',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Rate limit: 1 broadcast per 5 minutes per admin
        $cacheKey = 'broadcast_throttle_' . $request->user()->id;
        if (Cache::has($cacheKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before sending another broadcast. Limit: 1 per 5 minutes.',
            ], 429);
        }
        Cache::put($cacheKey, true, 300);

        $type = $request->input('type', 'all');

        AnnouncementCreated::dispatch(
            $request->title,
            $request->message,
            $type,
            $request->input('data', []),
        );

        return response()->json([
            'success' => true,
            'message' => 'Broadcast notification sent',
        ]);
    }

    /**
     * Advanced notification analytics dashboard.
     */
    public function dashboardAnalytics(Request $request)
    {
        $notifQuery = Notification::query();

        $total = (clone $notifQuery)->count();
        $unread = (clone $notifQuery)->where('is_read', false)->count();
        $read = $total - $unread;

        $byCategory = (clone $notifQuery)
            ->selectRaw('COALESCE(category, "uncategorized") as category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');

        $byPriority = (clone $notifQuery)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        // Analytics from notification_analytics table
        $sentCount = NotificationAnalytics::where('event_type', 'sent')->count();
        $openedCount = NotificationAnalytics::where('event_type', 'opened')->count();
        $failedCount = NotificationAnalytics::where('event_type', 'failed')->count();
        $openRate = $sentCount > 0 ? round(($openedCount / $sentCount) * 100, 1) : 0;

        $activeDevices = DeviceToken::where('is_active', true)->count();

        // Daily trend (last 14 days)
        $dailyTrend = NotificationAnalytics::where('event_type', 'sent')
            ->where('created_at', '>=', now()->subDays(14))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Open rate trend (last 7 days)
        $openTrend = NotificationAnalytics::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, event_type, COUNT(*) as count')
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($items) {
                $sent = $items->firstWhere('event_type', 'sent')?->count ?? 0;
                $opened = $items->firstWhere('event_type', 'opened')?->count ?? 0;
                return [
                    'sent' => $sent,
                    'opened' => $opened,
                    'rate' => $sent > 0 ? round(($opened / $sent) * 100, 1) : 0,
                ];
            });

        $recent = (clone $notifQuery)
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get(['id', 'user_id', 'title', 'category', 'priority', 'is_read', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'unread' => $unread,
                'read' => $read,
                'sent' => $sentCount,
                'opened' => $openedCount,
                'failed' => $failedCount,
                'open_rate' => $openRate,
                'active_devices' => $activeDevices,
                'by_category' => $byCategory,
                'by_priority' => $byPriority,
                'daily_trend' => $dailyTrend,
                'open_trend' => $openTrend,
                'recent' => $recent,
            ],
        ]);
    }
}
