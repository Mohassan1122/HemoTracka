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
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::with('organization');

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

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
            'organization_id' => ['required', 'exists:organizations,id'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'type' => ['required', 'in:Whole Blood,RBC,PLT,FFP,Cryo'],
            'units_in_stock' => ['required', 'integer', 'min:0'],
            'threshold' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $item = InventoryItem::create($validated);

        return response()->json([
            'message' => 'Inventory item created successfully',
            'item' => $item,
        ], 201);
    }

    /**
     * Display the specified inventory item.
     */
    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return response()->json([
            'item' => $inventoryItem->load('organization'),
        ]);
    }

    /**
     * Update the specified inventory item.
     */
    public function update(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $validated = $request->validate([
            'blood_group' => ['sometimes', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'type' => ['sometimes', 'in:Whole Blood,RBC,PLT,FFP,Cryo'],
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
    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
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
        $query = InventoryItem::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $summary = $query->selectRaw('blood_group, SUM(units_in_stock) as total_units')
            ->groupBy('blood_group')
            ->get();

        return response()->json([
            'summary' => $summary,
        ]);
    }
}
