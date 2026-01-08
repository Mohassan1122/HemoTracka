<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\RegulatoryBody;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegulatoryBodyMessagesController extends Controller
{
    /**
     * Get conversations list (PAGE 8 - Messages).
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');

            $query = Message::where(function ($q) use ($request) {
                $q->where('receiver_id', $request->user()->id)
                    ->orWhere('sender_id', $request->user()->id);
            })
            ->select(
                DB::raw('CASE WHEN sender_id = ' . $request->user()->id . ' THEN receiver_id ELSE sender_id END as other_user_id'),
                DB::raw('MAX(created_at) as last_message_at'),
                DB::raw('SUM(CASE WHEN receiver_id = ' . $request->user()->id . ' AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count')
            )
            ->groupBy('other_user_id')
            ->orderByDesc('last_message_at');

            if ($search) {
                // This is a simplified search - in production, you'd join with users/organizations
                $query->having('other_user_id', 'like', '%' . $search . '%');
            }

            $conversations = $query->paginate($perPage, ['*'], 'page', $page);

            // Load organization/user details
            $conversations->getCollection()->transform(function ($conv) {
                $otherUser = \App\Models\User::find($conv->other_user_id);
                $organization = Organization::where('user_id', $conv->other_user_id)->first();
                
                return [
                    'id' => $conv->other_user_id,
                    'organization_name' => $organization ? $organization->name : ($otherUser ? $otherUser->first_name : 'Unknown'),
                    'last_message_at' => $conv->last_message_at,
                    'unread_count' => $conv->unread_count,
                ];
            });

            return response()->json([
                'conversations' => $conversations->items(),
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                    'last_page' => $conversations->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get conversation thread (PAGE 8 - Conversation View).
     */
    public function getConversation(Request $request, $conversationId): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);

            // Get messages in conversation
            $messages = Message::where(function ($q) use ($request, $conversationId) {
                $q->where(function ($q2) use ($request, $conversationId) {
                    $q2->where('sender_id', $request->user()->id)
                        ->where('receiver_id', $conversationId);
                })->orWhere(function ($q2) use ($request, $conversationId) {
                    $q2->where('sender_id', $conversationId)
                        ->where('receiver_id', $request->user()->id);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

            // Mark messages as read
            Message::where('receiver_id', $request->user()->id)
                ->where('sender_id', $conversationId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            // Get conversation partner info
            $otherUser = \App\Models\User::find($conversationId);
            $organization = Organization::where('user_id', $conversationId)->first();

            return response()->json([
                'conversation' => [
                    'user_id' => $conversationId,
                    'name' => $organization ? $organization->name : ($otherUser ? $otherUser->first_name : 'Unknown'),
                    'organization_id' => $organization ? $organization->id : null,
                ],
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'last_page' => $messages->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send message (PAGE 8 - Send Message).
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => ['required', 'exists:users,id'],
            'content' => ['required', 'string'],
        ]);

        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $message = Message::create([
                'sender_id' => $request->user()->id,
                'receiver_id' => $request->receiver_id,
                'content' => $request->content,
            ]);

            return response()->json([
                'message' => 'Message sent successfully.',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create alert message (PAGE 8 - Create Alert).
     */
    public function createAlert(Request $request): JsonResponse
    {
        $request->validate([
            'blood_bank_ids' => ['required', 'array'],
            'blood_bank_ids.*' => ['exists:organizations,id'],
            'title' => ['required', 'string'],
            'content' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
        ]);

        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $messages = [];
            foreach ($request->blood_bank_ids as $bloodBankId) {
                $organization = Organization::find($bloodBankId);
                
                if ($organization && $organization->user_id) {
                    $message = Message::create([
                        'sender_id' => $request->user()->id,
                        'receiver_id' => $organization->user_id,
                        'content' => $request->content,
                        'subject' => '[ALERT] ' . $request->title,
                        'priority' => $request->priority ?? 'high',
                        'type' => 'alert',
                    ]);
                    
                    $messages[] = $message;
                }
            }

            return response()->json([
                'message' => 'Alert sent to ' . count($messages) . ' blood bank(s).',
                'alerts' => $messages,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark message as read (PAGE 8 - Mark as Read).
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        try {
            $message = Message::find($id);

            if (!$message || $message->receiver_id !== $request->user()->id) {
                return response()->json(['error' => 'Message not found or unauthorized.'], 404);
            }

            $message->update(['read_at' => now()]);

            return response()->json(['message' => 'Message marked as read.'], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
