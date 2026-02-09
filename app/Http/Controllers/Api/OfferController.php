<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\BloodRequest;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OfferController extends Controller
{
    /**
     * List offers for a specific blood request.
     */
    public function index(BloodRequest $bloodRequest): JsonResponse
    {
        // Fetch offers from Blood Banks
        $offers = $bloodRequest->offers()
            ->with('organization')
            ->orderBy('total_amount', 'asc')
            ->get();

        // Fetch appointments from Donors (via UserRequest)
        $donorAppointments = \App\Models\Appointment::whereIn('user_request_id', function ($query) use ($bloodRequest) {
            $query->select('id')
                ->from('users_requests')
                ->where('blood_request_id', $bloodRequest->id);
        })
            ->with('donor.user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $offers,
            'donor_appointments' => $donorAppointments
        ]);
    }

    /**
     * Check if the authenticated user/organization has already made an offer.
     */
    public function checkUserOffer(BloodRequest $bloodRequest): JsonResponse
    {
        $user = Auth::user();
        $orgId = $user->organization_id ?? $user->linkedOrganization?->id;

        if (!$orgId) {
            return response()->json([
                'success' => false,
                'has_offer' => false,
                'message' => 'No organization found'
            ]);
        }

        $offer = Offer::where('blood_request_id', $bloodRequest->id)
            ->where('organization_id', $orgId)
            ->first();

        return response()->json([
            'success' => true,
            'has_offer' => !!$offer,
            'offer' => $offer
        ]);
    }

    /**
     * Get all offers made by the authenticated blood bank.
     */
    public function getMyOffers(): JsonResponse
    {
        $user = Auth::user();
        $orgId = $user->organization_id ?? $user->linkedOrganization?->id;

        if (!$orgId) {
            return response()->json([
                'success' => false,
                'message' => 'No organization found'
            ], 400);
        }

        $offers = Offer::where('organization_id', $orgId)
            ->with(['bloodRequest.organization', 'bloodRequest.delivery'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $offers
        ]);
    }

    /**
     * Blood Bank submits an offer for a blood request.
     */
    public function store(Request $request, BloodRequest $bloodRequest): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isBloodBank()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'product_fee' => 'required|numeric|min:0',
            'shipping_fee' => 'required|numeric|min:0',
            'card_charge' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $productFee = $request->product_fee;
        $shippingFee = $request->shipping_fee;
        $cardCharge = $request->card_charge ?? 0;
        $totalAmount = $productFee + $shippingFee + $cardCharge;

        // Resolve organization ID
        $organizationId = $user->organization_id ?? $user->linkedOrganization?->id;

        if (!$organizationId) {
            return response()->json(['success' => false, 'message' => 'No organization linked to account'], 400);
        }

        $offer = Offer::create([
            'blood_request_id' => $bloodRequest->id,
            'organization_id' => $organizationId,
            'product_fee' => $productFee,
            'shipping_fee' => $shippingFee,
            'card_charge' => $cardCharge,
            'total_amount' => $totalAmount,
            'status' => 'Pending',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Offer submitted successfully',
            'data' => $offer
        ], 201);
    }

    /**
     * Hospital accepts an offer.
     */
    public function accept(Offer $offer): JsonResponse
    {
        $user = Auth::user();
        $bloodRequest = $offer->bloodRequest;

        $userOrgId = $user->organization_id ?? $user->linkedOrganization?->id; // Fix: Robust org ID resolution

        // Verify that the user belongs to the organization that made the request
        if ($userOrgId !== $bloodRequest->organization_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($offer->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'This offer cannot be accepted'], 400);
        }

        // 1. Mark offer as accepted
        $offer->update(['status' => 'Accepted']);

        // 2. Mark other pending offers for this request as rejected
        $bloodRequest->offers()
            ->where('id', '!=', $offer->id)
            ->where('status', 'Pending')
            ->update(['status' => 'Rejected']);

        // 3. Update Blood Request status and financial details
        $bloodRequest->update([
            'status' => 'Approved',
            'product_fee' => $offer->product_fee,
            'shipping_fee' => $offer->shipping_fee,
            'card_charge' => $offer->card_charge,
            'total_amount' => $offer->total_amount,
        ]);

        // 4. Create Delivery record (Triggering the delivery flow)
        $delivery = Delivery::create([
            'blood_request_id' => $bloodRequest->id,
            'pickup_location' => $offer->organization->address,
            'dropoff_location' => $bloodRequest->organization->address,
            'status' => 'Pending',
            'status_history' => [
                ['status' => 'Order Taken', 'time' => now()->toISOString()],
                ['status' => 'Preparing for Dispatch', 'time' => now()->addMinutes(5)->toISOString()],
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Offer accepted and delivery initiated',
            'data' => [
                'offer' => $offer,
                'delivery' => $delivery
            ]
        ]);
    }

    /**
     * Hospital rejects an offer.
     */
    public function reject(Offer $offer): JsonResponse
    {
        $user = Auth::user();
        $userOrgId = $user->organization_id ?? $user->linkedOrganization?->id; // Fix: Robust org ID resolution

        if ($userOrgId !== $offer->bloodRequest->organization_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $offer->update(['status' => 'Rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Offer rejected'
        ]);
    }
}
