<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FacilitiesController extends Controller
{
    /**
     * Get facilities-specific dashboard data.
     * For hospitals/regulatory bodies that REQUEST blood.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ensure user is a facility
        if ($user->role !== 'facilities') {
            return response()->json([
                'message' => 'Access denied. This endpoint is for facilities only.',
            ], 403);
        }

        // Get stats for the facility
        $stats = [
            'total_requests' => BloodRequest::where('organization_id', $user->organization_id)->count(),
            'pending_requests' => BloodRequest::where('organization_id', $user->organization_id)
                ->where('status', 'Pending')->count(),
            'approved_requests' => BloodRequest::where('organization_id', $user->organization_id)
                ->where('status', 'Approved')->count(),
            'completed_requests' => BloodRequest::where('organization_id', $user->organization_id)
                ->where('status', 'Completed')->count(),
            'in_transit' => BloodRequest::where('organization_id', $user->organization_id)
                ->where('status', 'In Transit')->count(),
        ];

        // Recent requests
        $recentRequests = BloodRequest::where('organization_id', $user->organization_id)
            ->with('delivery')
            ->latest()
            ->limit(5)
            ->get();

        // Requests by blood group
        $requestsByBloodGroup = BloodRequest::where('organization_id', $user->organization_id)
            ->selectRaw('blood_group, COUNT(*) as count, SUM(units_needed) as total_units')
            ->groupBy('blood_group')
            ->get();

        // Recent deliveries
        $recentDeliveries = Delivery::whereHas('bloodRequest', function ($query) use ($user) {
            $query->where('organization_id', $user->organization_id);
        })
            ->with(['bloodRequest', 'rider.user'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_requests' => $recentRequests,
            'requests_by_blood_group' => $requestsByBloodGroup,
            'recent_deliveries' => $recentDeliveries,
            'quick_actions' => [
                ['title' => 'Create Request', 'icon' => 'plus-circle', 'route' => 'blood-requests.create'],
                ['title' => 'Track Delivery', 'icon' => 'truck', 'route' => 'deliveries.track'],
                ['title' => 'Reports', 'icon' => 'file-text', 'route' => 'reports.index'],
            ]
        ]);
    }

    /**
     * Get detailed request history for facilities.
     */
    public function requestHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities') {
            return response()->json([
                'message' => 'Access denied. This endpoint is for facilities only.',
            ], 403);
        }

        $query = BloodRequest::where('organization_id', $user->organization_id)
            ->with(['delivery.rider.user']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by blood group
        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by urgency
        if ($request->has('urgency_level')) {
            $query->where('urgency_level', $request->urgency_level);
        }

        $requests = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($requests);
    }

    /**
     * Get reports overview for facilities.
     */
    public function reportsOverview(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities') {
            return response()->json([
                'message' => 'Access denied. This endpoint is for facilities only.',
            ], 403);
        }

        $organizationId = $user->organization_id;
        $year = $request->get('year', date('Y'));

        // Monthly request trends
        $monthlyRequests = BloodRequest::where('organization_id', $organizationId)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(units_needed) as total_units')
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();

        // Requests by status
        $requestsByStatus = BloodRequest::where('organization_id', $organizationId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Request fulfillment rate
        $totalRequests = BloodRequest::where('organization_id', $organizationId)->count();
        $completedRequests = BloodRequest::where('organization_id', $organizationId)
            ->where('status', 'Completed')->count();
        $fulfillmentRate = $totalRequests > 0 ? round(($completedRequests / $totalRequests) * 100, 2) : 0;

        // Average time to fulfillment (for completed requests)
        $avgFulfillmentTime = Delivery::whereHas('bloodRequest', function ($query) use ($organizationId) {
            $query->where('organization_id', $organizationId);
        })
            ->where('status', 'Delivered')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->first();

        // Top requested blood groups
        $topBloodGroups = BloodRequest::where('organization_id', $organizationId)
            ->selectRaw('blood_group, COUNT(*) as request_count, SUM(units_needed) as total_units')
            ->groupBy('blood_group')
            ->orderByDesc('request_count')
            ->limit(5)
            ->get();

        return response()->json([
            'year' => $year,
            'monthly_requests' => $monthlyRequests,
            'requests_by_status' => $requestsByStatus,
            'fulfillment_rate' => $fulfillmentRate,
            'avg_fulfillment_hours' => $avgFulfillmentTime->avg_hours ?? 0,
            'top_blood_groups' => $topBloodGroups,
            'summary' => [
                'total_requests' => $totalRequests,
                'completed_requests' => $completedRequests,
            ],
        ]);
    }

    /**
     * Search available blood inventory across blood banks.
     */
    public function searchBloodInventory(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities') {
            return response()->json([
                'message' => 'Access denied. This endpoint is for facilities only.',
            ], 403);
        }

        $query = InventoryItem::with('organization')
            ->whereHas('organization', function ($q) {
                $q->where('type', 'Blood Bank')
                    ->where('status', 'Active');
            })
            ->where('units_in_stock', '>', 0);

        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('min_units')) {
            $query->where('units_in_stock', '>=', $request->min_units);
        }

        $inventory = $query->orderByDesc('units_in_stock')->get();

        // Group by blood bank
        $groupedByOrg = $inventory->groupBy('organization_id')->map(function ($items, $orgId) {
            $org = $items->first()->organization;
            return [
                'organization' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'address' => $org->address,
                    'phone' => $org->phone,
                ],
                'available_blood' => $items->map(fn($item) => [
                    'blood_group' => $item->blood_group,
                    'type' => $item->type,
                    'units_available' => $item->units_in_stock,
                    'expiry_date' => $item->expiry_date,
                ]),
            ];
        })->values();

        return response()->json([
            'blood_banks' => $groupedByOrg,
        ]);
    }

    /**
     * Get list of staff members in the facility.
     */
    public function users(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities') {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }

        $users = User::where('organization_id', $user->organization_id)
            ->where('id', '!=', $user->id)
            ->get();

        return response()->json($users);
    }

    /**
     * Add a new staff member to the facility.
     */
    public function addUser(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities') {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:facilities'], // For now, only facilities role
        ]);

        $newUser = User::create([
            'organization_id' => $user->organization_id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Staff member added successfully',
            'user' => $newUser,
        ], 201);
    }

    /**
     * Update a staff member's details.
     */
    public function updateUser(Request $request, User $staffMember): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities' || $staffMember->organization_id !== $user->organization_id) {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $staffMember->id],
            'phone' => ['sometimes', 'string', 'max:20'],
            'status' => ['sometimes', 'string'],
        ]);

        $staffMember->update($validated);

        return response()->json([
            'message' => 'Staff member updated successfully',
            'user' => $staffMember,
        ]);
    }

    /**
     * Remove a staff member from the facility.
     */
    public function deleteUser(Request $request, User $staffMember): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities' || $staffMember->organization_id !== $user->organization_id) {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }

        $staffMember->delete();

        return response()->json([
            'message' => 'Staff member removed successfully',
        ]);
    }

    /**
     * Update the facility's organization profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities') {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }

        $organization = $user->organization;

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string'],
            'operating_hours' => ['sometimes', 'array'],
            'description' => ['sometimes', 'string'],
            'services' => ['sometimes', 'array'],
            'facebook_link' => ['sometimes', 'string', 'url'],
            'twitter_link' => ['sometimes', 'string', 'url'],
            'instagram_link' => ['sometimes', 'string', 'url'],
            'linkedin_link' => ['sometimes', 'string', 'url'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'logo' => ['sometimes', 'image', 'max:2048'],
            'cover_photo' => ['sometimes', 'image', 'max:5120'], // Larger size limit for cover photos
        ]);

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $filename = time() . '_logo_' . $image->getClientOriginalName();
            $path = $image->storeAs('organization_logos', $filename, 'public');
            $validated['logo'] = $path;
        }

        // Handle cover photo upload if provided
        if ($request->hasFile('cover_photo')) {
            $image = $request->file('cover_photo');
            $filename = time() . '_cover_' . $image->getClientOriginalName();
            $path = $image->storeAs('organization_covers', $filename, 'public');
            $validated['cover_photo'] = $path;
        }

        // Prepare update data excluding file fields if they weren't provided
        $updateData = [];
        foreach ($validated as $key => $value) {
            if (!in_array($key, ['logo', 'cover_photo']) || $value !== null) {
                $updateData[$key] = $value;
            }
        }

        $organization->update($updateData);

        return response()->json([
            'message' => 'Organization profile updated successfully',
            'data' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->type,
                'role' => $organization->role ?? 'facilities',
                'email' => $organization->contact_email,
                'phone' => $organization->phone,
                'address' => $organization->address,
                'logo_url' => $organization->logo_url,
                'cover_photo_url' => $organization->cover_photo_url,
                'description' => $organization->description,
                'services' => $organization->services,
                'operating_hours' => $organization->operating_hours,
                'facebook_link' => $organization->facebook_link,
                'twitter_link' => $organization->twitter_link,
                'instagram_link' => $organization->instagram_link,
                'linkedin_link' => $organization->linkedin_link,
                'latitude' => $organization->latitude,
                'longitude' => $organization->longitude,
            ]
        ]);
    }

    /**
     * Export request history (CSV mockup).
     */
    public function exportRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'facilities') {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }

        // In a real app, this would generate a CSV and return a download link or stream.
        // For now, we'll return a success message and dummy data.
        return response()->json([
            'message' => 'Export generated successfully',
            'download_url' => '/exports/blood-requests-' . now()->timestamp . '.csv',
            'format' => 'CSV',
        ]);
    }

    /**
     * Get all facilities (Hospitals and Blood Banks) for map display.
     * Optimized: Only returns facilities with valid coordinates.
     * No pagination - returns all data for fast map rendering.
     */
    public function getAllFacilities(Request $request): JsonResponse
    {
        $query = Organization::whereIn('type', ['Hospital', 'Blood Bank'])
            ->where('status', 'Active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Return only essential map fields, no pagination
        $facilities = $query->select(['id', 'name', 'address', 'longitude', 'latitude', 'type'])
            ->get();

        return response()->json([
            'data' => $facilities,
            'total' => $facilities->count()
        ]);
    }
}
