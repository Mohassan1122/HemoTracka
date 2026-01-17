<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Delivery;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\InventoryItem;
use App\Models\Rider;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BloodBankController extends Controller
{
    /**
     * Dashboard stats for Blood Bank
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $stats = [
            'total_stock' => InventoryItem::where('organization_id', $orgId)->sum('units_in_stock'),
            'out_of_stock' => InventoryItem::where('organization_id', $orgId)->where('units_in_stock', 0)->count(),
            'incoming_stock' => BloodRequest::where('organization_id', $orgId)->where('status', 'In Transit')->sum('units_needed'),
            'pending_requests' => BloodRequest::where('status', 'Pending')->count(), // Global pending for others to see? No, probably filtered or related to specific needs.
            'accepted_requests' => BloodRequest::where('status', 'Approved')->whereHas('delivery', function ($q) use ($orgId) {
                // Simplified: requests we are fulfilling
            })->count(),
        ];

        $recentRequests = BloodRequest::where('status', 'Pending')
            ->with('organization')
            ->latest()
            ->limit(5)
            ->get();

        $recentDeliveries = Delivery::whereHas('bloodRequest', function ($q) use ($orgId) {
            $q->where('organization_id', $orgId);
        })->with(['bloodRequest', 'rider.user'])->latest()->limit(5)->get();

        return response()->json([
            'stats' => $stats,
            'recent_requests' => $recentRequests,
            'recent_deliveries' => $recentDeliveries,
            'quick_actions' => [
                ['title' => 'Manage Inventory', 'icon' => 'database', 'route' => 'blood-bank.inventory'],
                ['title' => 'View Requests', 'icon' => 'bell', 'count' => $stats['pending_requests'], 'route' => 'blood-bank.requests'],
                ['title' => 'Donations', 'icon' => 'heart', 'route' => 'blood-bank.donations'],
            ]
        ]);
    }

    /**
     * Categorized inventory
     */
    public function inventory(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $items = InventoryItem::where('organization_id', $orgId)->get();

        $inventory = [
            'Whole Blood' => $this->groupInventory($items, 'Whole Blood'),
            'Platelets' => $this->groupInventory($items, 'Platelets'),
            'Bone Marrow' => $this->groupInventory($items, 'Bone Marrow'),
        ];

        return response()->json([
            'success' => true,
            'data' => $inventory
        ]);
    }

    private function groupInventory($items, $type): array
    {
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
        $grouped = [];

        foreach ($bloodGroups as $group) {
            $item = $items->where('type', $type)->where('blood_group', $group)->first();
            $grouped[] = [
                'blood_group' => $group,
                'units' => $item ? $item->units_in_stock : 0,
                'status' => $item ? ($item->units_in_stock > 10 ? 'High' : ($item->units_in_stock > 0 ? 'Low' : 'Out of Stock')) : 'Out of Stock',
                'item_id' => $item ? $item->id : null
            ];
        }

        return $grouped;
    }

    /**
     * Donation history / Medical records
     */
    public function donations(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $donations = Donation::where('organization_id', $orgId)
            ->with('donor')
            ->latest()
            ->paginate(10);

        return response()->json($donations);
    }

    /**
     * Update Donor Health Info
     */
    public function updateDonorHealth(Request $request, $id): JsonResponse
    {
        $donor = Donor::findOrFail($id);

        $validated = $request->validate([
            'genotype' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'height' => 'nullable|string',
            'platelets_type' => 'nullable|string',
        ]);

        $donor->update($validated);

        return response()->json([
            'message' => 'Donor health info updated successfully',
            'donor' => $donor
        ]);
    }

    /**
     * List Pending/Accepted requests
     */
    public function requests(Request $request): JsonResponse
    {
        $status = $request->query('status', 'Pending');

        $requests = BloodRequest::where('status', $status)
            ->with('organization')
            ->latest()
            ->paginate(10);

        return response()->json($requests);
    }

    /**
     * Get blood requests sent to this organization (blood bank).
     * This returns organization_requests entries for the authenticated organization.
     */
    public function myRequests(Request $request): JsonResponse
    {
        // Get the authenticated user (could be User or Organization)
        $auth = $request->user();

        // Determine organization ID based on auth type (supports all patterns)
        $organizationId = null;

        if (get_class($auth) === 'App\Models\Organization') {
            // Authenticated as Organization directly (existing pattern)
            $organizationId = $auth->id;
        } elseif ($auth->organization_id) {
            // Authenticated as User with organization_id (staff pattern)
            $organizationId = $auth->organization_id;
        } elseif ($auth->linkedOrganization) {
            // Authenticated as User who owns an organization (new pattern)
            $organizationId = $auth->linkedOrganization->id;
        }

        if (!$organizationId) {
            return response()->json([
                'message' => 'No organization associated with this account',
                'data' => [],
            ], 200);
        }

        $query = \App\Models\OrganizationRequest::with(['bloodRequest.organization', 'bloodRequest.delivery'])
            ->where('organization_id', $organizationId);

        // Filter by read status
        if ($request->has('is_read')) {
            $isRead = filter_var($request->get('is_read'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        // Filter by request status
        if ($request->has('status')) {
            $query->whereHas('bloodRequest', function ($q) use ($request) {
                $q->where('status', $request->get('status'));
            });
        }

        $organizationRequests = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($organizationRequests);
    }

    /**
     * Mark a specific organization request as read.
     */
    public function markRequestAsRead(Request $request, $id): JsonResponse
    {
        $auth = $request->user();
        $organizationId = null;

        if (get_class($auth) === 'App\Models\Organization') {
            $organizationId = $auth->id;
        } elseif ($auth->organization_id) {
            $organizationId = $auth->organization_id;
        } elseif ($auth->linkedOrganization) {
            $organizationId = $auth->linkedOrganization->id;
        }

        $organizationRequest = \App\Models\OrganizationRequest::where('id', $id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$organizationRequest) {
            return response()->json([
                'message' => 'Request not found or unauthorized',
            ], 404);
        }

        $organizationRequest->markAsRead();

        // Also increment the view count on the blood request
        $organizationRequest->bloodRequest->incrementViewCount();

        return response()->json([
            'message' => 'Request marked as read',
            'organization_request' => $organizationRequest->load('bloodRequest'),
        ]);
    }

    /**
     * Get request statistics for authenticated organization.
     */
    public function requestStats(Request $request): JsonResponse
    {
        $auth = $request->user();
        $organizationId = null;

        if (get_class($auth) === 'App\Models\Organization') {
            $organizationId = $auth->id;
        } elseif ($auth->organization_id) {
            $organizationId = $auth->organization_id;
        } elseif ($auth->linkedOrganization) {
            $organizationId = $auth->linkedOrganization->id;
        }

        $stats = [
            'total_requests' => \App\Models\OrganizationRequest::where('organization_id', $organizationId)->count(),
            'unread_requests' => \App\Models\OrganizationRequest::where('organization_id', $organizationId)->where('is_read', false)->count(),
            'read_requests' => \App\Models\OrganizationRequest::where('organization_id', $organizationId)->where('is_read', true)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Accept a blood request
     */
    public function acceptRequest(Request $request, $id): JsonResponse
    {
        $bloodRequest = BloodRequest::findOrFail($id);

        if ($bloodRequest->status !== 'Pending') {
            return response()->json(['message' => 'Request already processed'], 422);
        }

        $bloodRequest->update(['status' => 'Approved']);

        return response()->json([
            'message' => 'Request accepted successfully',
            'request' => $bloodRequest
        ]);
    }

    /**
     * Delivery Lifecycle
     */
    public function deliveries(Request $request): JsonResponse
    {
        $statusGroup = $request->query('type', 'ongoing'); // ongoing or history

        $query = Delivery::with(['bloodRequest.organization', 'rider.user']);

        if ($statusGroup === 'ongoing') {
            $query->whereNotIn('status', ['Delivered', 'Cancelled']);
        } else {
            $query->whereIn('status', ['Delivered', 'Cancelled']);
        }

        return response()->json($query->latest()->paginate(10));
    }

    /**
     * Assign rider and set fees
     */
    public function confirmDelivery(Request $request, $id): JsonResponse
    {
        $bloodRequest = BloodRequest::findOrFail($id);

        $validated = $request->validate([
            'rider_id' => 'required|exists:riders,id',
            'product_fee' => 'required|numeric',
            'shipping_fee' => 'required|numeric',
            'card_charge' => 'required|numeric',
        ]);

        $total = $validated['product_fee'] + $validated['shipping_fee'] + $validated['card_charge'];

        $bloodRequest->update([
            'product_fee' => $validated['product_fee'],
            'shipping_fee' => $validated['shipping_fee'],
            'card_charge' => $validated['card_charge'],
            'total_amount' => $total,
            'status' => 'Approved'
        ]);

        // Create or update delivery
        $delivery = Delivery::updateOrCreate(
            ['blood_request_id' => $id],
            [
                'rider_id' => $validated['rider_id'],
                'status' => 'Order Taken',
                'pickup_location' => $request->user()->organization->address ?? 'Blood Bank',
                'dropoff_location' => $bloodRequest->organization->address ?? 'Hospital',
            ]
        );

        return response()->json([
            'message' => 'Delivery confirmed and rider assigned',
            'delivery' => $delivery->load('rider.user')
        ]);
    }

    /**
     * Update delivery status timeline
     */
    public function updateDeliveryStatus(Request $request, $id): JsonResponse
    {
        $delivery = Delivery::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string', // Order Taken, Preparing, Product Ready, En Route, Delivered
        ]);

        $delivery->update(['status' => $validated['status']]);

        // If delivered, update blood request as well
        if ($validated['status'] === 'Delivered') {
            $delivery->bloodRequest->update(['status' => 'Completed']);
            $delivery->update(['delivery_time' => now()]);
        }

        return response()->json([
            'message' => 'Delivery status updated',
            'delivery' => $delivery
        ]);
    }

    /**
     * Blood Bank Settings
     */
    public function settings(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        if ($request->isMethod('put')) {
            $validated = $request->validate([
                'receive_notifications' => 'nullable|boolean',
                'show_inventory' => 'nullable|boolean',
                'show_contact' => 'nullable|boolean',
                'facebook_link' => 'nullable|url',
                'twitter_link' => 'nullable|url',
                'instagram_link' => 'nullable|url',
                'linkedin_link' => 'nullable|url',
            ]);

            $org->update($validated);
            return response()->json([
                'message' => 'Settings updated',
                'data' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'email' => $org->email,
                    'phone' => $org->phone,
                    'type' => $org->type,
                    'role' => $org->role ?? 'blood_banks',
                    'license_number' => $org->license_number,
                    'address' => $org->address,
                    'status' => $org->status,
                    'logo_url' => $org->logo_url,
                    'cover_photo_url' => $org->cover_photo_url,
                    'receive_notifications' => $org->receive_notifications ?? false,
                    'show_inventory' => $org->show_inventory ?? false,
                    'show_contact' => $org->show_contact ?? false,
                    'facebook_link' => $org->facebook_link,
                    'twitter_link' => $org->twitter_link,
                    'instagram_link' => $org->instagram_link,
                    'linkedin_link' => $org->linkedin_link,
                ]
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $org->id,
                'name' => $org->name,
                'email' => $org->email,
                'phone' => $org->phone,
                'type' => $org->type,
                'role' => $org->role ?? 'blood_banks',
                'license_number' => $org->license_number,
                'address' => $org->address,
                'status' => $org->status,
                'logo_url' => $org->logo_url,
                'cover_photo_url' => $org->cover_photo_url,
                'receive_notifications' => $org->receive_notifications ?? false,
                'show_inventory' => $org->show_inventory ?? false,
                'show_contact' => $org->show_contact ?? false,
                'facebook_link' => $org->facebook_link,
                'twitter_link' => $org->twitter_link,
                'instagram_link' => $org->instagram_link,
                'linkedin_link' => $org->linkedin_link,
            ]
        ]);
    }

    /**
     * Update blood bank profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'blood_banks') {
            return response()->json([
                'message' => 'Access denied. This endpoint is for blood banks only.',
            ], 403);
        }

        // Support both old (organization_id) and new (linkedOrganization) auth patterns
        $organization = $user->organization ?? $user->linkedOrganization;

        if (!$organization) {
            return response()->json([
                'message' => 'No organization associated with this account',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string'],
            'operating_hours' => ['sometimes', 'array'],
            'description' => ['sometimes', 'nullable', 'string'],
            'services' => ['sometimes', 'array'],
            'facebook_link' => ['sometimes', 'nullable', 'string'],
            'twitter_link' => ['sometimes', 'nullable', 'string'],
            'instagram_link' => ['sometimes', 'nullable', 'string'],
            'linkedin_link' => ['sometimes', 'nullable', 'string'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
        ]);

        // Validate logo only if it's actually a file upload
        if ($request->hasFile('logo')) {
            $request->validate(['logo' => ['image', 'max:2048']]);
        }

        // Validate cover_photo only if it's actually a file upload
        if ($request->hasFile('cover_photo')) {
            $request->validate(['cover_photo' => ['image', 'max:5120']]);
        }

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
            'message' => 'Blood bank profile updated successfully',
            'data' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->type,
                'role' => $organization->role ?? 'blood_banks',
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
     * Upload logo for the authenticated blood bank.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        $auth = $request->user();
        $organization = null;

        if (get_class($auth) === 'App\Models\Organization') {
            $organization = $auth;
        } elseif ($auth->organization_id) {
            $organization = \App\Models\Organization::find($auth->organization_id);
        } elseif ($auth->linkedOrganization) {
            $organization = $auth->linkedOrganization;
        }

        if (!$organization) {
            return response()->json([
                'message' => 'No organization associated with this account',
            ], 403);
        }

        // Delete old logo if exists
        if ($organization->logo && \Storage::disk('public')->exists($organization->logo)) {
            \Storage::disk('public')->delete($organization->logo);
        }

        // Store new logo
        $image = $request->file('logo');
        $filename = time() . '_logo_' . $organization->id . '_' . $image->getClientOriginalName();
        $path = $image->storeAs('organization_logos', $filename, 'public');

        $organization->update(['logo' => $path]);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'logo_url' => $organization->logo_url,
        ]);
    }

    /**
     * Upload cover photo for the authenticated blood bank.
     */
    public function uploadCoverPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'cover_photo' => ['required', 'image', 'max:5120'], // Max 5MB
        ]);

        $auth = $request->user();
        $organization = null;

        if (get_class($auth) === 'App\Models\Organization') {
            $organization = $auth;
        } elseif ($auth->organization_id) {
            $organization = \App\Models\Organization::find($auth->organization_id);
        } elseif ($auth->linkedOrganization) {
            $organization = $auth->linkedOrganization;
        }

        if (!$organization) {
            return response()->json([
                'message' => 'No organization associated with this account',
            ], 403);
        }

        // Delete old cover photo if exists
        if ($organization->cover_photo && \Storage::disk('public')->exists($organization->cover_photo)) {
            \Storage::disk('public')->delete($organization->cover_photo);
        }

        // Store new cover photo
        $image = $request->file('cover_photo');
        $filename = time() . '_cover_' . $organization->id . '_' . $image->getClientOriginalName();
        $path = $image->storeAs('organization_covers', $filename, 'public');

        $organization->update(['cover_photo' => $path]);

        return response()->json([
            'message' => 'Cover photo uploaded successfully',
            'cover_photo_url' => $organization->cover_photo_url,
        ]);
    }
}
