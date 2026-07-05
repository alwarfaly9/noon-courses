<?php

namespace App\Http\Controllers;

use App\Models\UserNotificationPreference;
use Illuminate\Http\Request;

/**
 * Handles the notification preferences center and the notification inbox.
 * The actual inbox lives in NotificationController (if any) — this only
 * manages user preferences for the smart engine.
 */
class NotificationPreferenceController extends Controller
{
    /**
     * GET /api/v1/student/notification-preferences
     */
    public function show(Request $request)
    {
        $prefs = UserNotificationPreference::forUser($request->user()->id);
        return response()->json(['success' => true, 'data' => $prefs]);
    }

    /**
     * PATCH /api/v1/student/notification-preferences
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'push_enabled'            => 'sometimes|boolean',
            'email_enabled'           => 'sometimes|boolean',
            'in_app_enabled'          => 'sometimes|boolean',
            'streak_reminders'        => 'sometimes|boolean',
            'inactivity_reminders'    => 'sometimes|boolean',
            'achievement_alerts'      => 'sometimes|boolean',
            'quiz_retry_reminders'    => 'sometimes|boolean',
            'path_reminders'          => 'sometimes|boolean',
            'community_replies'       => 'sometimes|boolean',
            'teacher_announcements'   => 'sometimes|boolean',
            'recommended_content'     => 'sometimes|boolean',
            'quiet_hour_start'        => 'sometimes|integer|min:0|max:23',
            'quiet_hour_end'          => 'sometimes|integer|min:0|max:23',
        ]);

        $prefs = UserNotificationPreference::forUser($request->user()->id);
        $prefs->update($validated);

        return response()->json(['success' => true, 'data' => $prefs]);
    }
}
