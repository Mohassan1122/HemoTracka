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
     * Get all blood requests created by the authenticated organization.
     * Returns requests with view_count to show how many have viewed them.
     */
    public function myCreatedRequests(Request $request): JsonResponse
    {
        $auth = $request->user();

        // Determine organization ID based on auth type (supports all patterns)
        $organizationId = null;

        if (get_class($auth) === 'App\Models\Organization') {
            // Authenticated as Organization directly (existing pattern)
            $organizationId = $auth->id;
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

        $query = BloodRequest::with(['organization', 'delivery', 'userRequests', 'organizationRequests'])
            ->where('organization_id', $organizationId);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by blood group
        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        // Filter by type
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

        // Set urgency level derived from is_emergency if not provided
        if (!isset($validated['urgency_level'])) {
            $validated['urgency_level'] = $validated['is_emergency'] ? 'Critical' : 'Normal';
        }

        // Resolve organization ID from authenticated user
        $user = $request->user();
        if ($user->linkedOrganization) {
            $validated['organization_id'] = $user->linkedOrganization->id;
        } else {
            return response()->json([
                'message' => 'User is not linked to any organization. Cannot create blood request.',
            ], 403);
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
        $organizationsToNotify = collect();

        // Get donor users
        if ($requestSource === 'donors' || $requestSource === 'both') {
            $usersToNotify = $usersToNotify->merge(User::where('role', 'donor')->get());
        }

        // Get blood bank organizations (blood banks are Organizations, not Users)
        // Exclude the organization that created the request - they shouldn't receive their own request
        if ($requestSource === 'blood_banks' || $requestSource === 'both') {
            $organizationsToNotify = $organizationsToNotify->merge(
                \App\Models\Organization::where('role', 'blood_banks')
                    ->where('status', 'Active')
                    ->where('id', '!=', $bloodRequest->organization_id) // Exclude requester
                    ->get()
            );
        }

        // Create user_request entries for users (donors)
        foreach ($usersToNotify as $user) {
            UserRequest::updateOrCreate(
                [
                    'blood_request_id' => $bloodRequest->id,
                    'user_id' => $user->id,
                ],
                [
                    'request_source' => $requestSource,
                    'is_read' => false,
                ]
            );
        }

        // Create organization_request entries for organizations (blood banks)
        foreach ($organizationsToNotify as $organization) {
            \App\Models\OrganizationRequest::updateOrCreate(
                [
                    'blood_request_id' => $bloodRequest->id,
                    'organization_id' => $organization->id,
                ],
                [
                    'request_source' => $requestSource,
                    'is_read' => false,
                ]
            );
        }

        // Send notifications to Users (donors) - wrapped in try-catch for email rate limits
        if ($usersToNotify->count() > 0) {
            try {
                Notification::send($usersToNotify, new NewBloodRequestNotification($bloodRequest));
            } catch (\Exception $e) {
                // Log error but don't fail the request - database entries are already created
                \Log::warning('Failed to send notifications to donors: ' . $e->getMessage());
            }
        }

        // Send notifications to Organizations (Blood Banks) - wrapped in try-catch for email rate limits
        if ($organizationsToNotify->count() > 0) {
            try {
                Notification::send($organizationsToNotify, new NewBloodRequestNotification($bloodRequest));
            } catch (\Exception $e) {
                // Log error but don't fail the request - database entries are already created
                \Log::warning('Failed to send notifications to blood banks: ' . $e->getMessage());
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
            ->where('user_id', $user->id)
            ->where('status', 'Pending'); // Only show pending requests that haven't been responded to

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
     * Get a single user request by ID.
     */
    public function showUserRequest(Request $request, UserRequest $userRequest): JsonResponse
    {
        // Check if the user owns this request
        if ($userRequest->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'user_request' => $userRequest->load('bloodRequest.organization'),
            'blood_request' => $userRequest->bloodRequest->load('organization'),
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

