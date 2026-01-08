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
}
