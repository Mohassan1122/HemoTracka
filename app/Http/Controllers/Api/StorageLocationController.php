<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StorageLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Helper to get organization ID
     */
    private function getOrganizationId($user)
    {
        if ($user->organization_id) {
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
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['data' => []]);
        }

        $query = StorageLocation::where('organization_id', $orgId)
            ->with(['subLocations', 'parentLocation']);

        if ($request->has('type')) {
            $query->where('location_type', $request->type);
        }

        if ($request->has('parent_id')) {
            $query->where('parent_location_id', $request->parent_id);
        } else if ($request->query('hierarchy') === 'true') {
            // Only root locations (no parent) if requesting hierarchy view
            $query->whereNull('parent_location_id');
        }

        $locations = $query->get();

        return response()->json(['data' => $locations]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'location_type' => 'required|in:room,fridge,freezer,shelf,container',
            'parent_location_id' => 'nullable|exists:storage_locations,id',
            'capacity' => 'nullable|integer|min:0',
            'min_temperature' => 'nullable|numeric',
            'max_temperature' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['message' => 'User not associated with organization'], 403);
        }

        $validated['organization_id'] = $orgId;

        $location = StorageLocation::create($validated);

        return response()->json([
            'message' => 'Location created successfully',
            'data' => $location
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, StorageLocation $storageLocation): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if ($storageLocation->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $storageLocation->load(['subLocations', 'parentLocation', 'inventoryItems']);

        // Append calculated attribute
        $storageLocation->append('full_path');

        return response()->json(['data' => $storageLocation]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StorageLocation $storageLocation): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if ($storageLocation->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'location_type' => 'sometimes|in:room,fridge,freezer,shelf,container',
            'parent_location_id' => 'nullable|exists:storage_locations,id',
            'capacity' => 'nullable|integer|min:0',
            'min_temperature' => 'nullable|numeric',
            'max_temperature' => 'nullable|numeric',
            'current_temperature' => 'nullable|numeric',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $storageLocation->update($validated);

        return response()->json([
            'message' => 'Location updated successfully',
            'data' => $storageLocation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, StorageLocation $storageLocation): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if ($storageLocation->organization_id !== $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if has inventory items
        if ($storageLocation->inventoryItems()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete location containing inventory items'
            ], 422);
        }

        $storageLocation->delete();

        return response()->json(['message' => 'Location deleted successfully']);
    }
}
