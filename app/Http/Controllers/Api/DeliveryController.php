<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Notifications\DeliveryStatusNotification;
use App\Events\DeliveryStatusChanged;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Display a listing of deliveries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Delivery::with(['bloodRequest.organization', 'rider.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }

        $deliveries = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($deliveries);
    }

    /**
     * Display the specified delivery.
     */
    public function show(Delivery $delivery): JsonResponse
    {
        return response()->json([
            'delivery' => $delivery->load(['bloodRequest.organization', 'rider.user']),
        ]);
    }

    /**
     * Update the specified delivery.
     */
    public function update(Request $request, Delivery $delivery): JsonResponse
    {
        $validated = $request->validate([
            'rider_id' => ['sometimes', 'exists:riders,id'],
            'pickup_location' => ['sometimes', 'string'],
            'dropoff_location' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:Pending,Assigned,Picked Up,In Transit,Delivered,Returned,Failed'],
        ]);

        $delivery->update($validated);

        return response()->json([
            'message' => 'Delivery updated successfully',
            'delivery' => $delivery->fresh(),
        ]);
    }

    /**
     * Assign a rider to the delivery.
     */
    public function assignRider(Request $request, Delivery $delivery): JsonResponse
    {
        $validated = $request->validate([
            'rider_id' => ['required', 'exists:riders,id'],
        ]);

        $delivery->update([
            'rider_id' => $validated['rider_id'],
            'status' => 'Assigned',
        ]);

        return response()->json([
            'message' => 'Rider assigned successfully',
            'delivery' => $delivery->fresh()->load('rider.user'),
        ]);

        // Notify Rider
        if ($delivery->rider && $delivery->rider->user) {
            $delivery->rider->user->notify(new DeliveryStatusNotification($delivery, 'Assigned'));
        }
    }

    /**
     * Mark delivery as picked up.
     */
    public function pickUp(Delivery $delivery): JsonResponse
    {
        if ($delivery->status !== 'Assigned') {
            return response()->json([
                'message' => 'Delivery must be assigned before pickup',
            ], 422);
        }

        $statusHistory = $delivery->status_history ?? [];
        $statusHistory[] = ['status' => 'Picked Up', 'time' => now()->toISOString()];

        $delivery->update([
            'status' => 'Picked Up',
            'pickup_time' => now(),
            'status_history' => $statusHistory,
        ]);

        $delivery->bloodRequest->update(['status' => 'In Transit']);

        // Broadcast Event
        event(new DeliveryStatusChanged($delivery, 'Picked Up'));

        return response()->json([
            'message' => 'Delivery marked as picked up',
            'delivery' => $delivery->fresh(),
        ]);

        // Broadcast Event
        event(new DeliveryStatusChanged($delivery, 'Picked Up'));

        // Notify Organization
        if ($delivery->bloodRequest && $delivery->bloodRequest->organization) {
            Notification::send($delivery->bloodRequest->organization->users, new DeliveryStatusNotification($delivery, 'Picked Up'));
        }
    }

    /**
     * Mark delivery as in transit.
     */
    public function inTransit(Delivery $delivery): JsonResponse
    {
        $statusHistory = $delivery->status_history ?? [];
        $statusHistory[] = ['status' => 'In Transit', 'time' => now()->toISOString()];

        $delivery->update([
            'status' => 'In Transit',
            'status_history' => $statusHistory,
        ]);

        // Broadcast Event
        event(new DeliveryStatusChanged($delivery, 'In Transit'));

        return response()->json([
            'message' => 'Delivery marked as in transit',
            'delivery' => $delivery->fresh(),
        ]);

        // Broadcast Event
        event(new DeliveryStatusChanged($delivery, 'In Transit'));

        // Notify Organization
        if ($delivery->bloodRequest && $delivery->bloodRequest->organization) {
            Notification::send($delivery->bloodRequest->organization->users, new DeliveryStatusNotification($delivery, 'In Transit'));
        }
    }

    /**
     * Mark delivery as delivered.
     */
    public function complete(Delivery $delivery): JsonResponse
    {
        $statusHistory = $delivery->status_history ?? [];
        $statusHistory[] = ['status' => 'Delivered', 'time' => now()->toISOString()];

        $delivery->update([
            'status' => 'Delivered',
            'delivery_time' => now(),
            'status_history' => $statusHistory,
        ]);

        $delivery->bloodRequest->update(['status' => 'Completed']);

        // Broadcast Event
        event(new DeliveryStatusChanged($delivery, 'Delivered'));

        return response()->json([
            'message' => 'Delivery completed successfully',
            'delivery' => $delivery->fresh(),
        ]);

        // Broadcast Event
        event(new DeliveryStatusChanged($delivery, 'Delivered'));

        // Notify Organization
        if ($delivery->bloodRequest && $delivery->bloodRequest->organization) {
            Notification::send($delivery->bloodRequest->organization->users, new DeliveryStatusNotification($delivery, 'Delivered'));
        }
    }

    /**
     * Confirm receipt of delivery (Receiver side - Hospital).
     */
    public function confirmReceipt(Delivery $delivery): JsonResponse
    {
        $statusHistory = $delivery->status_history ?? [];
        $statusHistory[] = ['status' => 'Received', 'time' => now()->toISOString()];

        $delivery->update([
            'receiver_confirmed_at' => now(),
            'status_history' => $statusHistory,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery receipt confirmed',
            'delivery' => $delivery->fresh()
        ]);
    }

    /**
     * Get the delivery timeline.
     */
    public function timeline(Delivery $delivery): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $delivery->status_history ?? []
        ]);
    }

    /**
     * Track delivery by tracking code.
     */
    public function track(string $trackingCode): JsonResponse
    {
        $delivery = Delivery::where('tracking_code', $trackingCode)
            ->with(['bloodRequest.organization', 'rider.user'])
            ->first();

        if (!$delivery) {
            return response()->json([
                'message' => 'Delivery not found',
            ], 404);
        }

        return response()->json([
            'delivery' => $delivery,
        ]);
    }
}
