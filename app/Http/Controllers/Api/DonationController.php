<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    /**
     * Display a listing of donations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Donation::with(['donor', 'organization']);

        if ($request->has('donor_id')) {
            $query->where('donor_id', $request->donor_id);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $donations = $query->latest('donation_date')->paginate($request->get('per_page', 15));

        return response()->json($donations);
    }

    /**
     * Store a newly created donation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donor_id' => ['required', 'exists:donors,id'],
            'organization_id' => ['required', 'exists:organizations,id'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'units' => ['required', 'integer', 'min:1'],
            'donation_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:Pending,Screened,Stored,Discarded,Used'],
        ]);

        $donation = Donation::create($validated);

        // Update donor's last donation date
        $donor = Donor::find($validated['donor_id']);
        $donor->update(['last_donation_date' => $validated['donation_date']]);

        return response()->json([
            'message' => 'Donation recorded successfully',
            'donation' => $donation->load(['donor', 'organization']),
        ], 201);
    }

    /**
     * Display the specified donation.
     */
    public function show(Donation $donation): JsonResponse
    {
        return response()->json([
            'donation' => $donation->load(['donor', 'organization']),
        ]);
    }

    /**
     * Update the specified donation.
     */
    public function update(Request $request, Donation $donation): JsonResponse
    {
        $validated = $request->validate([
            'blood_group' => ['sometimes', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'units' => ['sometimes', 'integer', 'min:1'],
            'donation_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:Pending,Screened,Stored,Discarded,Used'],
        ]);

        $donation->update($validated);

        return response()->json([
            'message' => 'Donation updated successfully',
            'donation' => $donation->fresh(),
        ]);
    }

    /**
     * Remove the specified donation.
     */
    public function destroy(Donation $donation): JsonResponse
    {
        $donation->delete();

        return response()->json([
            'message' => 'Donation deleted successfully',
        ]);
    }
}
