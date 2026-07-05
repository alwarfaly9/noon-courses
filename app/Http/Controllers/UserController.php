<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Credit;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Get user profile
    public function profile(Request $request)
    {
        $user = $request->user()->load(['roles', 'credits']);
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    // Update profile
    public function update(Request $request, \App\Services\ProfileService $profileService)
    {
        try {
            $user = $profileService->updateProfile($request->user(), $request->all(), $request->ip());

            $userData = clone $user;
            $userData->load(['roles', 'credits']);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $userData
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }
    }

    // Admin: Get all users
    public function index(Request $request)
    {
        $query = User::with(['roles', 'credits']);

        if ($request->role) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $users = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    // Admin: Get user details
    public function show($id)
    {
        $user = User::with(['roles', 'credits', 'coursesAsTeacher', 'enrolledCourses'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    // Admin: Update user
    public function updateUser(\App\Http\Requests\UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load(['roles', 'credits'])
        ]);
    }

    // Admin: Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    // Admin: Toggle user status
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->forceFill(['is_active' => !$user->is_active])->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => $user
        ]);
    }

    // Get User Notifications
    public function notifications(Request $request)
    {
        $limit = $request->input('limit', 20);
        $notifications = \App\Models\Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    // Mark notification as read
    public function markNotificationRead(Request $request, $id)
    {
        $notification = \App\Models\Notification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Marked as read']);
    }

    // Mark all notifications as read
    public function markAllNotificationsRead(Request $request)
    {
        \App\Models\Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json(['success' => true, 'message' => 'All marked as read']);
    }
}
