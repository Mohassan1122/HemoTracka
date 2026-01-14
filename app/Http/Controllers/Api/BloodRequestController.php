<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Delivery;
use App\Models\User;
use App\Models\UserRequest;
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

        if ($request->has('is_emergency')) {
            $isEmergency = filter_var($request->get('is_emergency'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_emergency', $isEmergency);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
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
            'type' => ['required', 'in:Blood,Platelets,Bone Marrow'],
            'request_source' => ['required', 'in:donors,blood_banks,both'],
            'blood_group' => ['required_if:type,Blood', 'nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'genotype' => ['nullable', 'string', 'max:50'],
            'units_needed' => ['required', 'integer', 'min:1'],
            'min_units_bank_can_send' => ['nullable', 'integer', 'min:1'],
            'needed_by' => ['required', 'date'],
            'is_emergency' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        // Set default values
        $validated['status'] = 'Pending';
        if (!isset($validated['is_emergency'])) {
            $validated['is_emergency'] = false;
        }

        $bloodRequest = BloodRequest::create($validated);

        // Distribute request to users based on request_source
        $this->distributeRequestToUsers($bloodRequest);

        return response()->json([
            'message' => 'Blood request created successfully',
            'request' => $bloodRequest->load('organization'),
        ], 201);
    }

    /**
     * Distribute blood request to appropriate users based on request_source.
     */
    private function distributeRequestToUsers(BloodRequest $bloodRequest): void
    {
        $requestSource = $bloodRequest->request_source;
        $usersToNotify = collect();

        // Get users based on request source
        if ($requestSource === 'donors' || $requestSource === 'both') {
            $usersToNotify = $usersToNotify->merge(User::where('role', 'donor')->get());
        }

        if ($requestSource === 'blood_banks' || $requestSource === 'both') {
            $usersToNotify = $usersToNotify->merge(User::where('role', 'blood_banks')->get());
        }

        // Create user_request entries for each user
        foreach ($usersToNotify as $user) {
            UserRequest::create([
                'blood_request_id' => $bloodRequest->id,
                'user_id' => $user->id,
                'request_source' => $requestSource,
                'is_read' => false,
            ]);
        }

        // Send notifications to Users
        if ($usersToNotify->count() > 0) {
            Notification::send($usersToNotify, new NewBloodRequestNotification($bloodRequest));
        }

        // Also notify Organizations (Blood Banks) directly
        if ($requestSource === 'blood_banks' || $requestSource === 'both') {
            $bloodBankOrganizations = \App\Models\Organization::where('type', 'Blood Bank')
                ->where('status', 'Active')
                ->get();

            if ($bloodBankOrganizations->count() > 0) {
                Notification::send($bloodBankOrganizations, new NewBloodRequestNotification($bloodRequest));
            }
        }
    }

    /**
     * Record a view/read of a blood request.
     * This increments the view_count for tracking purposes.
     */
    public function recordView(BloodRequest $bloodRequest): JsonResponse
    {
        $bloodRequest->incrementViewCount();

        return response()->json([
            'message' => 'View recorded successfully',
            'view_count' => $bloodRequest->view_count,
        ]);
    }

    /**
     * Display the specified blood request and mark as read for authenticated user.
     */
    public function show(Request $request, BloodRequest $bloodRequest): JsonResponse
    {
        // Mark request as read for the authenticated user
        if ($request->user()) {
            $userRequest = UserRequest::where('blood_request_id', $bloodRequest->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($userRequest) {
                $userRequest->markAsRead();
            }
        }

        return response()->json([
            'request' => $bloodRequest->load(['organization', 'delivery.rider.user', 'userRequests']),
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

    /**
     * Get all blood requests for the authenticated user.
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = UserRequest::with(['bloodRequest.organization', 'bloodRequest.delivery'])
            ->where('user_id', $user->id);

        // Filter by read status
        if ($request->has('is_read')) {
            $isRead = filter_var($request->get('is_read'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        // Filter by request source
        if ($request->has('request_source')) {
            $query->where('request_source', $request->get('request_source'));
        }

        // Filter by request status
        if ($request->has('status')) {
            $query->whereHas('bloodRequest', function ($q) {
                $q->where('status', request()->get('status'));
            });
        }

        $userRequests = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($userRequests);
    }

    /**
     * Mark a specific user request as read.
     */
    public function markAsRead(Request $request, UserRequest $userRequest): JsonResponse
    {
        // Check if the user owns this request
        if ($userRequest->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $userRequest->markAsRead();

        return response()->json([
            'message' => 'Request marked as read',
            'user_request' => $userRequest->load('bloodRequest'),
        ]);
    }

    /**
     * Get request statistics for authenticated user.
     */
    public function requestStats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'total_requests' => UserRequest::where('user_id', $user->id)->count(),
            'unread_requests' => UserRequest::where('user_id', $user->id)->where('is_read', false)->count(),
            'read_requests' => UserRequest::where('user_id', $user->id)->where('is_read', true)->count(),
            'by_source' => [
                'donors' => UserRequest::where('user_id', $user->id)->where('request_source', 'donors')->count(),
                'blood_banks' => UserRequest::where('user_id', $user->id)->where('request_source', 'blood_banks')->count(),
                'both' => UserRequest::where('user_id', $user->id)->where('request_source', 'both')->count(),
            ],
        ];

        return response()->json($stats);
    }
}

