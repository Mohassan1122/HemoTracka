<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate($request->get('per_page', 15));

        return response()->json($notifications);
    }

    /**
     * Get unread notifications.
     */
    public function unread(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->paginate($request->get('per_page', 15));

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Get unread count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
