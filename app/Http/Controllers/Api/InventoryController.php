<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display a listing of inventory items.
     */
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
     * Display a listing of inventory items.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['data' => []]);
        }

        $query = InventoryItem::with('organization')
            ->where('organization_id', $orgId);

        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('units_in_stock', '<=', 'threshold');
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // 'organization_id' removed from required input, resolved server-side
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'type' => ['required', 'in:Whole Blood,RBC,PLT,FFP,Cryo,Platelets'],
            'units_in_stock' => ['required', 'integer', 'min:0'],
            'threshold' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json([
                'message' => 'User is not associated with any organization',
            ], 403);
        }

        $validated['organization_id'] = $orgId;

        // Check if inventory item already exists
        $item = InventoryItem::where('organization_id', $orgId)
            ->where('blood_group', $validated['blood_group'])
            ->where('type', $validated['type'])
            ->first();

        if ($item) {
            // Update existing item
            $item->increment('units_in_stock', $validated['units_in_stock']);

            // Update other fields if provided (optional)
            if (isset($validated['location'])) {
                $item->location = $validated['location'];
            }
            if (isset($validated['expiry_date'])) {
                $item->expiry_date = $validated['expiry_date'];
            }
            if (isset($validated['threshold'])) {
                $item->threshold = $validated['threshold'];
            }
            $item->save();

            $message = 'Inventory stock updated successfully';
        } else {
            // Create new item
            $item = InventoryItem::create($validated);
            $message = 'Inventory item created successfully';
        }

        return response()->json([
            'message' => $message,
            'item' => $item,
        ], 201);
    }

    /**
     * Display the specified inventory item.
     */
    public function show(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if ($inventoryItem->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'item' => $inventoryItem->load('organization'),
        ]);
    }

    /**
     * Update the specified inventory item.
     */
    public function update(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if ($inventoryItem->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'blood_group' => ['sometimes', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'type' => ['sometimes', 'in:Whole Blood,RBC,PLT,FFP,Cryo,Platelets'],
            'units_in_stock' => ['sometimes', 'integer', 'min:0'],
            'threshold' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $inventoryItem->update($validated);

        return response()->json([
            'message' => 'Inventory item updated successfully',
            'item' => $inventoryItem->fresh(),
        ]);
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if ($inventoryItem->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $inventoryItem->delete();

        return response()->json([
            'message' => 'Inventory item deleted successfully',
        ]);
    }

    /**
     * Adjust stock (add or remove units).
     */
    public function adjustStock(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if ($inventoryItem->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'adjustment' => ['required', 'integer'],
            'reason' => ['nullable', 'string'],
        ]);

        $newStock = $inventoryItem->units_in_stock + $validated['adjustment'];

        if ($newStock < 0) {
            return response()->json([
                'message' => 'Insufficient stock',
            ], 422);
        }

        $inventoryItem->update(['units_in_stock' => $newStock]);

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'item' => $inventoryItem->fresh(),
        ]);
    }

    /**
     * Get summary of inventory by blood group.
     */
    public function summary(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['summary' => []]);
        }

        $summary = InventoryItem::where('organization_id', $orgId)
            ->selectRaw('blood_group, SUM(units_in_stock) as total_units')
            ->groupBy('blood_group')
            ->get();

        return response()->json([
            'summary' => $summary,
        ]);
    }
}
