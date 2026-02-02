<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\NewMessageSent;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Display inbox messages.
     */
    public function inbox(Request $request): JsonResponse
    {
        $messages = Message::where('to_user_id', $request->user()->id)
            ->with('sender')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($messages);
    }

    /**
     * Display sent messages.
     */
    public function sent(Request $request): JsonResponse
    {
        $messages = Message::where('from_user_id', $request->user()->id)
            ->with('recipient')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($messages);
    }

    /**
     * Store a newly created message.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_user_id' => ['required', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $message = Message::create([
            'from_user_id' => $request->user()->id,
            'to_user_id' => $validated['to_user_id'],
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
        ]);

        // Broadcast message for real-time delivery
        event(new NewMessageSent($message));

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message->load('recipient'),
        ], 201);
    }

    /**
     * Display the specified message.
     */
    public function show(Request $request, Message $message): JsonResponse
    {
        // Check if user can view this message
        if ($message->from_user_id !== $request->user()->id && $message->to_user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Mark as read if recipient is viewing
        if ($message->to_user_id === $request->user()->id) {
            $message->markAsRead();
        }

        return response()->json([
            'message' => $message->load(['sender', 'recipient']),
        ]);
    }

    /**
     * Remove the specified message.
     */
    public function destroy(Request $request, Message $message): JsonResponse
    {
        if ($message->from_user_id !== $request->user()->id && $message->to_user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'message' => 'Message deleted successfully',
        ]);
    }

    /**
     * Get unread count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Message::where('to_user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Mark all messages as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Message::where('to_user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All messages marked as read',
        ]);
    }

    /**
     * Get conversations - list of users with last message.
     */
    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get unique users the current user has conversed with
        $conversations = \DB::table('messages')
            ->select('other_user_id')
            ->selectRaw('MAX(created_at) as last_message_time')
            ->fromSub(function ($query) use ($userId) {
                $query->select('from_user_id as other_user_id', 'created_at')
                    ->from('messages')
                    ->where('to_user_id', $userId)
                    ->union(
                        \DB::table('messages')
                            ->select('to_user_id as other_user_id', 'created_at')
                            ->where('from_user_id', $userId)
                    );
            }, 'all_messages')
            ->groupBy('other_user_id')
            ->orderByDesc('last_message_time')
            ->get();

        $conversationData = [];

        foreach ($conversations as $conv) {
            $otherUserId = $conv->other_user_id;

            // Get the last message
            $lastMessage = Message::where(function ($q) use ($userId, $otherUserId) {
                $q->where('from_user_id', $userId)->where('to_user_id', $otherUserId)
                    ->orWhere('from_user_id', $otherUserId)->where('to_user_id', $userId);
            })
                ->latest()
                ->first();

            // Get unread count
            $unreadCount = Message::where('from_user_id', $otherUserId)
                ->where('to_user_id', $userId)
                ->whereNull('read_at')
                ->count();

            // Get other user info
            $otherUser = \App\Models\User::with('linkedOrganization')->find($otherUserId);

            if ($otherUser) {
                $conversationData[] = [
                    'user_id' => $otherUserId,
                    'user' => [
                        'id' => $otherUser->id,
                        'first_name' => $otherUser->first_name,
                        'last_name' => $otherUser->last_name,
                        'role' => $otherUser->role,
                        'organization' => $otherUser->linkedOrganization ? [
                            'id' => $otherUser->linkedOrganization->id,
                            'name' => $otherUser->linkedOrganization->name,
                            'logo_url' => $otherUser->linkedOrganization->logo_url,
                        ] : null,
                    ],
                    'last_message' => $lastMessage ? [
                        'id' => $lastMessage->id,
                        'from_user_id' => $lastMessage->from_user_id,
                        'to_user_id' => $lastMessage->to_user_id,
                        'body' => $lastMessage->body,
                        'is_read' => $lastMessage->read_at !== null,
                        'created_at' => $lastMessage->created_at,
                    ] : null,
                    'unread_count' => $unreadCount,
                ];
            }
        }

        return response()->json([
            'data' => $conversationData,
        ]);
    }

    /**
     * Get chat with specific user.
     */
    public function chat(Request $request, $otherUserId): JsonResponse
    {
        $userId = $request->user()->id;

        // Get all messages between users
        $messages = Message::where(function ($q) use ($userId, $otherUserId) {
            $q->where('from_user_id', $userId)->where('to_user_id', $otherUserId)
                ->orWhere('from_user_id', $otherUserId)->where('to_user_id', $userId);
        })
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages from other user as read
        Message::where('from_user_id', $otherUserId)
            ->where('to_user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Get other user info
        $otherUser = \App\Models\User::with('linkedOrganization')->find($otherUserId);

        return response()->json([
            'data' => $messages,
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'first_name' => $otherUser->first_name,
                'last_name' => $otherUser->last_name,
                'role' => $otherUser->role,
                'organization' => $otherUser->linkedOrganization ? [
                    'id' => $otherUser->linkedOrganization->id,
                    'name' => $otherUser->linkedOrganization->name,
                    'logo_url' => $otherUser->linkedOrganization->logo_url,
                ] : null,
            ] : null,
        ]);
    }

    /**
     * Send message to specific user.
     */
    public function sendToUser(Request $request, $otherUserId): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $message = Message::create([
            'from_user_id' => $request->user()->id,
            'to_user_id' => $otherUserId,
            'body' => $validated['body'],
        ]);

        // Broadcast message for real-time delivery
        event(new NewMessageSent($message));

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message->load('sender'),
        ], 201);
    }
    /**
     * Search users to start new chat.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $search = $request->input('query');
        $userId = $request->user()->id;

        if (!$search || strlen($search) < 2) {
            return response()->json(['data' => []]);
        }

        $users = \App\Models\User::with('linkedOrganization')
            ->where('id', '!=', $userId) // Exclude current user
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('linkedOrganization', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
            })
            ->limit(10)
            ->get();

        $results = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
                'organization' => $user->linkedOrganization ? [
                    'id' => $user->linkedOrganization->id,
                    'name' => $user->linkedOrganization->name,
                    'logo_url' => $user->linkedOrganization->logo_url,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $results
        ]);
    }
}
