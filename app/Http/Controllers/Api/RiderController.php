<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderController extends Controller
{
    /**
     * Display a listing of riders.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Rider::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $riders = $query->paginate($request->get('per_page', 15));

        return response()->json($riders);
    }

    /**
     * Store a newly created rider.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:riders'],
            'vehicle_type' => ['required', 'in:Bike,Car,Van,Drone'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:Available,Busy,Offline'],
        ]);

        $rider = Rider::create($validated);

        return response()->json([
            'message' => 'Rider created successfully',
            'rider' => $rider->load('user'),
        ], 201);
    }

    /**
     * Display the specified rider.
     */
    public function show(Rider $rider): JsonResponse
    {
        return response()->json([
            'rider' => $rider->load(['user', 'deliveries']),
        ]);
    }

    /**
     * Update the specified rider.
     */
    public function update(Request $request, Rider $rider): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_type' => ['sometimes', 'in:Bike,Car,Van,Drone'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'current_latitude' => ['nullable', 'numeric'],
            'current_longitude' => ['nullable', 'numeric'],
            'status' => ['sometimes', 'in:Available,Busy,Offline'],
        ]);

        $rider->update($validated);

        return response()->json([
            'message' => 'Rider updated successfully',
            'rider' => $rider->fresh(),
        ]);
    }

    /**
     * Remove the specified rider.
     */
    public function destroy(Rider $rider): JsonResponse
    {
        $rider->delete();

        return response()->json([
            'message' => 'Rider deleted successfully',
        ]);
    }

    /**
     * Update rider location.
     */
    public function updateLocation(Request $request, Rider $rider): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $rider->update([
            'current_latitude' => $validated['latitude'],
            'current_longitude' => $validated['longitude'],
        ]);

        return response()->json([
            'message' => 'Location updated successfully',
            'rider' => $rider->fresh(),
        ]);
    }

    /**
     * Get available riders.
     */
    public function available(): JsonResponse
    {
        $riders = Rider::where('status', 'Available')
            ->with('user')
            ->get();

        return response()->json([
            'riders' => $riders,
        ]);
    }
}
