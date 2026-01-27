<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualityControlController extends Controller
{
    /**
     * Get items pending quality check
     */
    public function pending(Request $request): JsonResponse
    {
        $items = InventoryItem::where('organization_id', $request->user()->organization_id)
            ->where('quality_status', 'pending')
            ->with(['donor', 'donation'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $items]);
    }

    /**
     * Update quality status
     */
    public function updateStatus(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        if ($inventoryItem->organization_id !== $request->user()->organization_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:passed,failed,quarantine',
            'notes' => 'nullable|string|max:1000',
        ]);

        $inventoryItem->update([
            'quality_status' => $validated['status'],
            'quality_checked_at' => now(),
            'quality_checked_by' => $request->user()->id,
            'quality_notes' => $validated['notes'],
        ]);

        return response()->json([
            'message' => 'Quality status updated',
            'data' => $inventoryItem->fresh(['qualityCheckedBy']),
        ]);
    }

    /**
     * Get quality control statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $stats = [
            'pending' => InventoryItem::where('organization_id', $orgId)->where('quality_status', 'pending')->count(),
            'passed' => InventoryItem::where('organization_id', $orgId)->where('quality_status', 'passed')->count(),
            'failed' => InventoryItem::where('organization_id', $orgId)->where('quality_status', 'failed')->count(),
            'quarantine' => InventoryItem::where('organization_id', $orgId)->where('quality_status', 'quarantine')->count(),
        ];

        return response()->json($stats);
    }
}
