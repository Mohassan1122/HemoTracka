<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * List all unique conversations for the logged-in user.
     */
    public function conversations(): JsonResponse
    {
        $userId = Auth::id();

        $conversations = Message::where('from_user_id', $userId)
            ->orWhere('to_user_id', $userId)
            ->select(DB::raw('CASE WHEN from_user_id = ' . $userId . ' THEN to_user_id ELSE from_user_id END as other_user_id'), DB::raw('MAX(created_at) as last_message_time'))
            ->groupBy('other_user_id')
            ->orderBy('last_message_time', 'desc')
            ->get();

        $data = $conversations->map(function ($convo) use ($userId) {
            $otherUser = User::find($convo->other_user_id);
            if (!$otherUser)
                return null;

            $lastMessage = Message::where(function ($q) use ($userId, $otherUser) {
                $q->where('from_user_id', $userId)->where('to_user_id', $otherUser->id);
            })->orWhere(function ($q) use ($userId, $otherUser) {
                $q->where('from_user_id', $otherUser->id)->where('to_user_id', $userId);
            })->latest()->first();

            return [
                'user' => [
                    'id' => $otherUser->id,
                    'full_name' => $otherUser->full_name,
                    'role' => $otherUser->role,
                ],
                'last_message' => [
                    'content' => $lastMessage->content,
                    'time' => $lastMessage->created_at->diffForHumans(),
                    'is_read' => $lastMessage->is_read,
                    'is_from_me' => $lastMessage->from_user_id === $userId,
                ]
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get chat history with a specific user.
     */
    public function chat(User $otherUser): JsonResponse
    {
        $userId = Auth::id();

        $messages = Message::where(function ($q) use ($userId, $otherUser) {
            $q->where('from_user_id', $userId)->where('to_user_id', $otherUser->id);
        })->orWhere(function ($q) use ($userId, $otherUser) {
            $q->where('from_user_id', $otherUser->id)->where('to_user_id', $userId);
        })
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark incoming messages as read
        Message::where('from_user_id', $otherUser->id)
            ->where('to_user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => $messages->map(function ($m) use ($userId) {
                return [
                    'id' => $m->id,
                    'content' => $m->content,
                    'is_from_me' => $m->from_user_id === $userId,
                    'time' => $m->created_at->format('H:i'),
                    'full_time' => $m->created_at->toISOString(),
                ];
            })
        ]);
    }

    /**
     * Send a message to a user.
     */
    public function send(Request $request, User $otherUser): JsonResponse
    {
        $request->validate(['content' => 'required|string']);

        $message = Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $otherUser->id,
            'content' => $request->input('content'),
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $message
        ], 201);
    }
}
