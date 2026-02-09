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
     * Dashboard stats for Blood Bank
     */
    public function dashboard(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['message' => 'No organization found'], 404);
        }

        $stats = [
            'total_stock' => InventoryItem::where('organization_id', $orgId)->sum('units_in_stock'),
            'out_of_stock' => InventoryItem::where('organization_id', $orgId)->where('units_in_stock', 0)->count(),
            // Incoming stock are approved requests that are in transit/delivery
            'incoming_stock' => BloodRequest::where('organization_id', $orgId)->where('status', 'In Transit')->sum('units_needed'),
            // Requests sent TO this blood bank
            'pending_requests' => \App\Models\OrganizationRequest::where('organization_id', $orgId)->whereHas('bloodRequest', function ($q) {
                $q->where('status', 'Pending');
            })->count(),
            'accepted_requests' => BloodRequest::whereHas('offers', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId)->where('status', 'Accepted');
            })->count(),
        ];

        // Recent Requests: Fetch OrganizationRequests for this org where the underlying blood request is Pending
        $recentRequests = \App\Models\OrganizationRequest::where('organization_id', $orgId)
            ->whereHas('bloodRequest', function ($q) {
                $q->where('status', 'Pending');
            })
            ->with(['bloodRequest.organization.user']) // Load requester's user for messaging
            ->latest()
            ->limit(3)
            ->get();

        $recentDeliveries = Delivery::whereHas('bloodRequest', function ($q) use ($orgId) {
            $q->where('organization_id', $orgId); // Deliveries FOR requests made BY this org? Or deliveries FULFILLED by this org? 
            // Usually dashboard shows outbound deliveries if you are a blood bank fulfilling requests. 
            // "My Requests" (inbound) logic might differ. 
            // For now, let's assume this shows deliveries relevant to the org's operations.
        })->with(['bloodRequest', 'rider.user'])->latest()->limit(5)->get();

        return response()->json([
            'stats' => $stats,
            'recent_requests' => $recentRequests, // This now contains OrganizationRequest objects wrapping BloodRequest
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
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found'
            ], 404);
        }

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
     * Get Dashboard Statistics for Blood Bank
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('linkedOrganization');

        // Get the blood bank organization ID
        $organizationId = null;
        if ($user->role === 'blood_banks') {
            $organizationId = $user->linkedOrganization?->id;
        }

        if (!$organizationId) {
            return response()->json([
                'message' => 'Blood bank organization not found',
                'recent_requests' => []
            ], 404);
        }

        // Get recent requests sent to this blood bank (limit to 5)
        $recentRequests = \App\Models\OrganizationRequest::where('organization_id', $organizationId)
            ->where('status', 'Pending')
            ->with(['bloodRequest.organization'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($orgRequest) {
                $bloodRequest = $orgRequest->bloodRequest;
                if (!$bloodRequest)
                    return null;

                return [
                    'id' => $orgRequest->id,
                    'blood_request_id' => $bloodRequest->id,
                    'blood_group' => $bloodRequest->blood_group,
                    'units_requested' => $bloodRequest->units_needed,
                    'urgency_level' => $bloodRequest->urgency_level,
                    'request_type' => $bloodRequest->request_type,
                    'reason' => $bloodRequest->reason,
                    'patient_name' => $bloodRequest->patient_name,
                    'status' => $orgRequest->status,
                    'organization' => $bloodRequest->organization,
                    'created_at' => $bloodRequest->created_at,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'recent_requests' => $recentRequests,
        ]);
    }
    /**
     * Donation history / Medical records
     */
    public function donations(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json([
                'message' => 'Organization not found',
                'data' => []
            ], 404);
        }

        $donations = Donation::where('organization_id', $orgId)
            ->with('donor')
            ->latest()
            ->paginate(10);

        return response()->json($donations);
    }

    /**
     * Get Deliveries for the authenticated Blood Bank (both incoming and outgoing).
     */
    public function deliveries(Request $request): JsonResponse
    {
        $user = \Auth::user();
        $orgId = $user->organization_id ?? $user->linkedOrganization?->id;

        if (!$orgId) {
            return response()->json(['message' => 'No organization found'], 403);
        }

        $statusGroup = $request->query('type', 'ongoing'); // ongoing or history

        // Fetch deliveries where the organization is the sender (via BloodRequest) OR the receiver (via BloodRequest's organization)
        // Actually, in the current flow: 
        // - Sender (Provider) is the one who made the offer (Offer.organization_id = $orgId) -> Pickup Location
        // - Receiver is the one who made the request (BloodRequest.organization_id) -> Dropoff Location

        // Let's find deliveries linked to offers made by this org
        // OR deliveries linked to requests made by this org

        $query = Delivery::with(['bloodRequest.organization', 'rider.user'])
            ->where(function ($q) use ($orgId) {
                // As Provider (Sender - accepted offer)
                $q->whereHas('bloodRequest.offers', function ($q2) use ($orgId) {
                    $q2->where('organization_id', $orgId)
                        ->where('status', 'Accepted');
                })
                    // As Receiver (Requester)
                    ->orWhereHas('bloodRequest', function ($q2) use ($orgId) {
                    $q2->where('organization_id', $orgId);
                });
            });

        if ($statusGroup === 'ongoing') {
            $query->whereNotIn('status', ['Delivered', 'Cancelled']);
        } else {
            $query->whereIn('status', ['Delivered', 'Cancelled']);
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Get Donor Details
     */

    /**
     * Get Donor Details
     */
    public function getDonor($id): JsonResponse
    {
        $donor = Donor::with('user')->findOrFail($id);

        return response()->json([
            'donor' => [
                'id' => $donor->id,
                'first_name' => $donor->first_name,
                'last_name' => $donor->last_name,
                'other_names' => $donor->other_names,
                'email' => $donor->user->email ?? null,
                'phone' => $donor->phone,
                'blood_group' => $donor->blood_group,
                'genotype' => $donor->genotype,
                'height' => $donor->height,
                'date_of_birth' => $donor->date_of_birth,
                'address' => $donor->address,
                'last_donation_date' => $donor->last_donation_date,
                'status' => $donor->status,
                'instagram_handle' => $donor->instagram_handle,
                'twitter_handle' => $donor->twitter_handle,
            ]
        ]);
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
     * List Pending/Accepted requests for the authenticated blood bank
     */
    public function requests(Request $request): JsonResponse
    {
        $organizationId = $this->getOrganizationId($request->user());

        if (!$organizationId) {
            return response()->json([
                'message' => 'Blood bank organization not found',
                'data' => []
            ], 404);
        }

        $status = $request->query('status');

        // Get requests through OrganizationRequest pivot table
        $query = \App\Models\OrganizationRequest::where('organization_id', $organizationId)
            ->with(['bloodRequest.organization']);

        // Filter by status if provided
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->latest()
            ->paginate(20);

        // Transform the data to match frontend expectations
        $transformedData = collect($requests->items())->map(function ($orgRequest) {
            $bloodRequest = $orgRequest->bloodRequest;
            if (!$bloodRequest)
                return null;

            return [
                'id' => $bloodRequest->id,
                'blood_group' => $bloodRequest->blood_group,
                'units_requested' => $bloodRequest->units_needed,
                'urgency_level' => $bloodRequest->urgency_level,
                'request_type' => $bloodRequest->request_type,
                'reason' => $bloodRequest->reason,
                'patient_name' => $bloodRequest->patient_name,
                'status' => $orgRequest->status,
                'organization' => $bloodRequest->organization,
                'created_at' => $bloodRequest->created_at,
            ];
        })->filter(); // Remove nulls

        return response()->json([
            'data' => $transformedData->values(),
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
            'total' => $requests->total(),
        ]);
    }

    /**
     * Get blood requests sent to this organization (blood bank).
     * This returns organization_requests entries for the authenticated organization.
     */
    public function myRequests(Request $request): JsonResponse
    {
        $organizationId = $this->getOrganizationId($request->user());

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

    /**
     * Record a donation from a completed appointment.
     * This creates a Donation record and updates inventory.
     */
    public function recordDonation(Request $request, \App\Models\Appointment $appointment): JsonResponse
    {
        // Validate that the appointment belongs to this blood bank
        $auth = $request->user();
        $organizationId = null;

        if (get_class($auth) === 'App\Models\Organization') {
            $organizationId = $auth->id;
        } elseif ($auth->organization_id) {
            $organizationId = $auth->organization_id;
        } elseif ($auth->linkedOrganization) {
            $organizationId = $auth->linkedOrganization->id;
        }

        if (!$organizationId || $appointment->organization_id !== $organizationId) {
            return response()->json([
                'message' => 'Unauthorized: This appointment does not belong to your organization',
            ], 403);
        }

        // Check appointment status - must be Confirmed
        if ($appointment->status !== 'Confirmed') {
            return response()->json([
                'message' => 'Only confirmed appointments can have donations recorded',
            ], 422);
        }

        // Validate request data
        $validated = $request->validate([
            'units' => ['required', 'integer', 'min:1', 'max:10'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'doctor_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Get blood group from appointment or donor or request
        $bloodGroup = $validated['blood_group']
            ?? $appointment->blood_group
            ?? $appointment->donor->blood_group;

        if (!$bloodGroup) {
            return response()->json([
                'message' => 'Blood group is required. Please specify in the request.',
            ], 422);
        }

        // Start a database transaction
        \DB::beginTransaction();

        try {
            // 1. Create the Donation record
            $donation = Donation::create([
                'donor_id' => $appointment->donor_id,
                'organization_id' => $organizationId,
                'appointment_id' => $appointment->id,
                'blood_group' => $bloodGroup,
                'units' => $validated['units'],
                'donation_date' => now()->toDateString(),
                'notes' => $appointment->notes,
                'doctor_notes' => $validated['doctor_notes'] ?? null,
                'status' => 'Pending', // Will go through screening
            ]);

            // 2. Update the appointment status to Completed
            $appointment->update(['status' => 'Completed']);

            // 3. Update donor's last donation date
            $appointment->donor->update(['last_donation_date' => now()->toDateString()]);

            // 4. Auto-update Inventory (add to stock)
            $inventoryItem = InventoryItem::firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'blood_group' => $bloodGroup,
                    'type' => $appointment->donation_type === 'Platelets' ? 'PLT' : 'Whole Blood',
                ],
                [
                    'units_in_stock' => 0,
                    'threshold' => 10,
                ]
            );
            $inventoryItem->increment('units_in_stock', $validated['units']);

            // 5. Update UserRequest status if this appointment was from a blood request
            if ($appointment->user_request_id) {
                $userRequest = \App\Models\UserRequest::find($appointment->user_request_id);
                if ($userRequest) {
                    $userRequest->markAsFulfilled();

                    // 6. Update the BloodRequest units_fulfilled
                    $bloodRequest = $userRequest->bloodRequest;
                    if ($bloodRequest) {
                        $bloodRequest->addFulfilledUnits($validated['units']);
                    }
                }
            }

            \DB::commit();

            return response()->json([
                'message' => 'Donation recorded successfully',
                'donation' => $donation->load(['donor', 'appointment']),
                'inventory_updated' => true,
                'new_stock' => $inventoryItem->fresh()->units_in_stock,
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Failed to record donation: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to record donation: ' . $e->getMessage(),
            ], 500);
        }
    }
}
