<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComponentSeparationController extends Controller
{
    /**
     * Separate whole blood into components
     */
    public function separate(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        if ($inventoryItem->organization_id !== $request->user()->organization_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only whole blood can be separated
        if ($inventoryItem->type !== 'Whole Blood') {
            return response()->json(['message' => 'Only Whole Blood can be separated into components'], 422);
        }

        $validated = $request->validate([
            'components' => 'required|array|min:1',
            'components.*.type' => 'required|string|in:RBC,Plasma,Platelets,Cryo',
            'components.*.units' => 'required|integer|min:1',
        ]);

        $createdComponents = [];

        foreach ($validated['components'] as $component) {
            $newItem = InventoryItem::create([
                'organization_id' => $inventoryItem->organization_id,
                'blood_group' => $inventoryItem->blood_group,
                'type' => $component['type'],
                'units_in_stock' => $component['units'],
                'threshold' => 5,
                'location' => $inventoryItem->location,
                'expiry_date' => $this->getExpiryForComponent($component['type']),
                'storage_location_id' => $inventoryItem->storage_location_id,
                'quality_status' => 'pending',
                'donor_id' => $inventoryItem->donor_id,
                'donation_id' => $inventoryItem->donation_id,
                'parent_item_id' => $inventoryItem->id,
                'is_component' => true,
                'component_type' => $component['type'],
                'separated_at' => now(),
            ]);

            $createdComponents[] = $newItem;
        }

        // Reduce whole blood units
        $totalComponentUnits = array_sum(array_column($validated['components'], 'units'));
        $inventoryItem->decrement('units_in_stock', min($totalComponentUnits, $inventoryItem->units_in_stock));

        return response()->json([
            'message' => 'Components separated successfully',
            'components' => $createdComponents,
            'parent' => $inventoryItem->fresh(),
        ]);
    }

    /**
     * Get components derived from a parent item
     */
    public function getComponents(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        if ($inventoryItem->organization_id !== $request->user()->organization_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $components = $inventoryItem->childComponents()->get();

        return response()->json(['data' => $components]);
    }

    /**
     * Get component expiry based on type (industry standards)
     */
    private function getExpiryForComponent(string $type): \Carbon\Carbon
    {
        return match ($type) {
            'RBC' => now()->addDays(42),      // 42 days
            'Plasma' => now()->addYear(),      // 1 year frozen
            'Platelets' => now()->addDays(5),  // 5 days
            'Cryo' => now()->addYear(),        // 1 year frozen
            default => now()->addDays(35),
        };
    }
}
