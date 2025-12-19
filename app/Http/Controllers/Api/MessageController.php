<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
