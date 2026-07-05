<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // Get list of conversations for the current user,
    // with per-conversation unread count derived from last_read_at.
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Private conversations the user participates in
        $privateChats = Conversation::where('type', 'private')
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with([
                'participants.user:id,name,avatar',
                'lastMessage',
            ])
            ->get();

        // 2. Course group chats (enrolled students + their teachers)
        $courseIds = [];
        if ($user->hasRole('teacher')) {
            $courseIds = $user->coursesAsTeacher()->pluck('id')->toArray();
        } else {
            $courseIds = $user->enrolledCourses()->pluck('courses.id')->toArray();
        }

        // Lazy-create group conversations for each course
        foreach ($courseIds as $courseId) {
            Conversation::firstOrCreate(
                ['type' => 'course_group', 'course_id' => $courseId],
                ['last_message_at' => now()]
            );
        }

        $groupChats = Conversation::where('type', 'course_group')
            ->whereIn('course_id', $courseIds)
            ->with([
                'course:id,title,image',
                'lastMessage',
            ])
            ->get();

        // 3. Merge, sort, and annotate with unread count
        $allChats = $privateChats->merge($groupChats)
            ->sortByDesc('last_message_at')
            ->values()
            ->map(function (Conversation $conv) use ($user) {
                // Find when this user last read the conversation
                $participant = ConversationParticipant::where('conversation_id', $conv->id)
                    ->where('user_id', $user->id)
                    ->first();

                $lastReadAt = $participant?->last_read_at;

                $unreadCount = $lastReadAt
                    ? $conv->messages()
                        ->where('created_at', '>', $lastReadAt)
                        ->where('user_id', '!=', $user->id)
                        ->count()
                    : $conv->messages()
                        ->where('user_id', '!=', $user->id)
                        ->count();

                $conv->setAttribute('unread_count', $unreadCount);
                return $conv;
            });

        return response()->json([
            'success' => true,
            'data' => $allChats,
        ]);
    }

    // Get messages for a conversation (paginated, newest first)
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $conversation = Conversation::with(['participants'])->findOrFail($id);

        $this->authorizeAccess($user, $conversation);

        $messages = $conversation->messages()
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    // Send a message (text or image)
    public function store(\App\Http\Requests\StoreChatRequest $request, $id)
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        $this->authorizeAccess($user, $conversation);

        $data = $request->validated();

        $type = 'text';
        $body = $request->body;

        if ($request->hasFile('image')) {
            $type = 'image';
            $path = $request->file('image')->store('chat', 'public');
            // Build URL from request host so the URL is reachable on any
            // network interface (avoids APP_URL missing port issue).
            $body = $request->getSchemeAndHttpHost() . '/storage/' . $path;
        }

        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'body'    => $body,
            'type'    => $type,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_id' => $message->id,
        ]);

        // Update sender's last_read_at so their own new message doesn't count
        // as unread for themselves.
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        return response()->json([
            'success' => true,
            'data'    => $message->load('user:id,name,avatar'),
        ]);
    }

    // Mark a conversation as read by the current user.
    // Updates last_read_at on the participant record.
    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        $this->authorizeAccess($user, $conversation);

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        // For private chats, also create a participant record if missing
        // (course_group conversations don't have explicit participant rows).
        if ($conversation->type === 'private') {
            ConversationParticipant::firstOrCreate(
                ['conversation_id' => $conversation->id, 'user_id' => $user->id],
                ['last_read_at' => now()]
            );
        }

        return response()->json(['success' => true]);
    }

    // Start (or retrieve) a private 1-to-1 conversation with another user.
    public function startPrivateChat(Request $request)
    {
        $user = $request->user();
        $request->validate(['user_id' => 'required|exists:users,id']);
        $targetUserId = (int) $request->user_id;

        if ($user->id === $targetUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot start a chat with yourself',
            ], 400);
        }

        // Find existing conversation
        $conversation = Conversation::where('type', 'private')
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->whereHas('participants', fn($q) => $q->where('user_id', $targetUserId))
            ->first();

        if (!$conversation) {
            DB::transaction(function () use ($user, $targetUserId, &$conversation) {
                $conversation = Conversation::create(['type' => 'private']);
                $conversation->participants()->createMany([
                    ['user_id' => $user->id],
                    ['user_id' => $targetUserId],
                ]);
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $conversation->load('participants.user:id,name,avatar'),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function authorizeAccess(User $user, Conversation $conversation): void
    {
        if ($conversation->type === 'private') {
            if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
                abort(403, 'Unauthorized access to this chat.');
            }
        } elseif ($conversation->type === 'course_group') {
            $isEnrolled = $user->enrolledCourses()
                ->where('courses.id', $conversation->course_id)
                ->exists();
            $isTeacher  = $user->coursesAsTeacher()
                ->where('id', $conversation->course_id)
                ->exists();

            if (!$isEnrolled && !$isTeacher && !$user->hasRole('admin')) {
                abort(403, 'You must be enrolled in this course to join the chat.');
            }
        }
    }
}

