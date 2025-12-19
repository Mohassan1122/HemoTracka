<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\NewOrganizationNotification;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Organization::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $organizations = $query->paginate($request->get('per_page', 15));

        return response()->json($organizations);
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:Hospital,Blood Bank,Regulatory Body,Logistics'],
            'license_number' => ['required', 'string', 'max:100', 'unique:organizations'],
            'address' => ['required', 'string'],
            'contact_email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'logo' => ['nullable', 'string'],
            'status' => ['nullable', 'in:Pending,Active,Suspended'],
        ]);

        $organization = Organization::create($validated);

        // Notify admins
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new NewOrganizationNotification($organization));

        return response()->json([
            'message' => 'Organization created successfully. Admin will review your registration.',
            'organization' => $organization,
        ], 201);
    }

    /**
     * Display the specified organization.
     */
    public function show(Organization $organization): JsonResponse
    {
        return response()->json([
            'organization' => $organization->load(['users', 'inventoryItems']),
        ]);
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:Hospital,Blood Bank,Regulatory Body,Logistics'],
            'license_number' => ['sometimes', 'string', 'max:100', 'unique:organizations,license_number,' . $organization->id],
            'address' => ['sometimes', 'string'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'logo' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:Pending,Active,Suspended'],
        ]);

        $organization->update($validated);

        return response()->json([
            'message' => 'Organization updated successfully',
            'organization' => $organization->fresh(),
        ]);
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(Organization $organization): JsonResponse
    {
        $organization->delete();

        return response()->json([
            'message' => 'Organization deleted successfully',
        ]);
    }

    /**
     * Get organization statistics.
     */
    public function stats(Organization $organization): JsonResponse
    {
        return response()->json([
            'total_donors' => $organization->donors()->count(),
            'total_donations' => $organization->donations()->count(),
            'total_inventory_items' => $organization->inventoryItems()->sum('units_in_stock'),
            'pending_requests' => $organization->bloodRequests()->where('status', 'Pending')->count(),
        ]);
    }

    /**
     * Get all blood banks.
     */
    public function bloodBanks(Request $request): JsonResponse
    {
        $query = Organization::where('type', 'Blood Bank')
            ->where('status', 'Active');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $bloodBanks = $query->paginate($request->get('per_page', 15));

        return response()->json($bloodBanks);
    }

    /**
     * Find nearby blood banks using location.
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:100'], // km
        ]);

        $lat = $validated['latitude'];
        $lng = $validated['longitude'];
        $radius = $validated['radius'] ?? 25; // Default 25km radius

        // Haversine formula to calculate distance
        $bloodBanks = Organization::where('type', 'Blood Bank')
            ->where('status', 'Active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'address' => $org->address,
                    'phone' => $org->phone,
                    'latitude' => $org->latitude,
                    'longitude' => $org->longitude,
                    'distance_km' => round($org->distance, 2),
                    'operating_hours' => $org->operating_hours,
                    'services' => $org->services,
                ];
            });

        return response()->json([
            'blood_banks' => $bloodBanks,
            'search_location' => [
                'latitude' => $lat,
                'longitude' => $lng,
            ],
            'radius_km' => $radius,
            'total' => $bloodBanks->count(),
        ]);
    }
}
