<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Delivery;
use App\Models\User;
use App\Notifications\NewBloodRequestNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloodRequestController extends Controller
{
    /**
     * Display a listing of blood requests.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BloodRequest::with(['organization', 'delivery']);

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        if ($request->has('urgency_level')) {
            $query->where('urgency_level', $request->urgency_level);
        }

        $requests = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($requests);
    }

    /**
     * Store a newly created blood request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'units_needed' => ['required', 'integer', 'min:1'],
            'patient_name' => ['nullable', 'string', 'max:100'],
            'hospital_unit' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:Emergent,Bulk,Routine'],
            'urgency_level' => ['required', 'in:Critical,High,Normal'],
            'needed_by' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $bloodRequest = BloodRequest::create($validated);

        // Notify Blood Banks and Admins
        $usersToNotify = User::whereIn('role', ['blood_bank', 'admin'])->get();
        if ($usersToNotify->count() > 0) {
            Notification::send($usersToNotify, new NewBloodRequestNotification($bloodRequest));
        }

        return response()->json([
            'message' => 'Blood request created successfully',
            'request' => $bloodRequest->load('organization'),
        ], 201);
    }

    /**
     * Display the specified blood request.
     */
    public function show(BloodRequest $bloodRequest): JsonResponse
    {
        return response()->json([
            'request' => $bloodRequest->load(['organization', 'delivery.rider.user']),
        ]);
    }

    /**
     * Update the specified blood request.
     */
    public function update(Request $request, BloodRequest $bloodRequest): JsonResponse
    {
        $validated = $request->validate([
            'blood_group' => ['sometimes', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'units_needed' => ['sometimes', 'integer', 'min:1'],
            'patient_name' => ['nullable', 'string', 'max:100'],
            'hospital_unit' => ['nullable', 'string', 'max:50'],
            'type' => ['sometimes', 'in:Emergent,Bulk,Routine'],
            'urgency_level' => ['sometimes', 'in:Critical,High,Normal'],
            'needed_by' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:Pending,Approved,Sourcing,In Transit,Completed,Cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $bloodRequest->update($validated);

        return response()->json([
            'message' => 'Blood request updated successfully',
            'request' => $bloodRequest->fresh(),
        ]);
    }

    /**
     * Remove the specified blood request.
     */
    public function destroy(BloodRequest $bloodRequest): JsonResponse
    {
        $bloodRequest->delete();

        return response()->json([
            'message' => 'Blood request deleted successfully',
        ]);
    }

    /**
     * Approve a blood request and create delivery.
     */
    public function approve(Request $request, BloodRequest $bloodRequest): JsonResponse
    {
        if ($bloodRequest->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending requests can be approved',
            ], 422);
        }

        $validated = $request->validate([
            'pickup_location' => ['required', 'string'],
            'dropoff_location' => ['required', 'string'],
        ]);

        $bloodRequest->update(['status' => 'Approved']);

        $delivery = Delivery::create([
            'blood_request_id' => $bloodRequest->id,
            'pickup_location' => $validated['pickup_location'],
            'dropoff_location' => $validated['dropoff_location'],
            'status' => 'Pending',
        ]);

        return response()->json([
            'message' => 'Blood request approved and delivery created',
            'request' => $bloodRequest->fresh()->load('delivery'),
        ]);
    }

    /**
     * Cancel a blood request.
     */
    public function cancel(BloodRequest $bloodRequest): JsonResponse
    {
        if (in_array($bloodRequest->status, ['Completed', 'Cancelled'])) {
            return response()->json([
                'message' => 'Cannot cancel a completed or already cancelled request',
            ], 422);
        }

        $bloodRequest->update(['status' => 'Cancelled']);

        return response()->json([
            'message' => 'Blood request cancelled successfully',
            'request' => $bloodRequest->fresh(),
        ]);
    }
}
