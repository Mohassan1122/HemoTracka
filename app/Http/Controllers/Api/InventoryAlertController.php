<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryAlert;
use App\Services\InventoryAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryAlertController extends Controller
{
    protected $alertService;

    public function __construct(InventoryAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Helper to get organization ID
     */
    private function getOrganizationId($user)
    {
        if (isset($user->organization_id) && $user->organization_id) {
            return $user->organization_id;
        }

        if (get_class($user) === 'App\Models\Organization') {
            return $user->id;
        }

        if ($user->linkedOrganization) {
            return $user->linkedOrganization->id;
        }

        return null;
    }

    /**
     * Get all alerts for the organization
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['data' => []]);
        }

        $query = InventoryAlert::with(['inventoryItem', 'acknowledgedByUser'])
            ->where('organization_id', $orgId)
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Filter by acknowledged status
        if ($request->has('is_acknowledged')) {
            $query->where('is_acknowledged', $request->boolean('is_acknowledged'));
        }

        // Filter by severity
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by alert type
        if ($request->has('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        $alerts = $query->paginate($request->get('per_page', 20));

        return response()->json($alerts);
    }

    /**
     * Get unread alerts count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        $count = InventoryAlert::where('organization_id', $orgId)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get alerts summary (counts by severity)
     */
    public function summary(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());
        $summary = $this->alertService->getAlertsSummary($orgId);

        return response()->json($summary);
    }

    /**
     * Mark alert as read
     */
    public function markAsRead(Request $request, InventoryAlert $alert): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        // Verify ownership
        if ($alert->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $alert->markAsRead();

        return response()->json([
            'message' => 'Alert marked as read',
            'alert' => $alert->fresh(),
        ]);
    }

    /**
     * Mark all alerts as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        InventoryAlert::where('organization_id', $orgId)
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All alerts marked as read']);
    }

    /**
     * Acknowledge alert
     */
    public function acknowledge(Request $request, InventoryAlert $alert): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        // Verify ownership
        if ($alert->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $alert->acknowledge($request->user()->id, $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Alert acknowledged',
            'alert' => $alert->fresh(['acknowledgedByUser']),
        ]);
    }

    /**
     * Manually trigger alert check (for testing or manual refresh)
     */
    public function checkAlerts(Request $request): JsonResponse
    {
        $newAlerts = $this->alertService->checkAndGenerateAlerts();

        return response()->json([
            'message' => 'Alert check completed',
            'new_alerts_count' => count($newAlerts),
            'alerts' => $newAlerts,
        ]);
    }

    /**
     * Delete/dismiss an alert
     */
    public function destroy(Request $request, InventoryAlert $alert): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        // Verify ownership
        if ($alert->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $alert->delete();

        return response()->json(['message' => 'Alert deleted']);
    }
}
