<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Appointment::with(['donor', 'organization']);

        // If user is a donor, only show their appointments
        if ($user->role === 'donor' && $user->donor) {
            $query->where('donor_id', $user->donor->id);
        }

        // If user belongs to an organization, show that org's appointments
        if (get_class($user) === 'App\Models\Organization') {
            $query->where('organization_id', $user->id);
        } elseif ($user->organization_id) {
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->linkedOrganization) {
            $query->where('organization_id', $user->linkedOrganization->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('donation_type', $request->type); // Using donation_type for simplicity in this context or add a dedicated type field if needed.
        }

        if ($request->has('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        $appointments = $query->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate($request->get('per_page', 15));

        return response()->json($appointments);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'donation_type' => ['nullable', 'string'],
            'type' => ['nullable', 'in:Walk-in,Scheduled'],
            'user_request_id' => ['nullable', 'exists:users_requests,id'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'genotype' => ['nullable', 'in:AA,AS,SS,AC'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        if (!$user->donor) {
            return response()->json(['message' => 'User is not a donor.'], 403);
        }

        $validated['donor_id'] = $user->donor->id;

        // Check organization is a Blood Bank
        $organization = Organization::findOrFail($validated['organization_id']);
        if ($organization->type !== 'Blood Bank') {
            return response()->json([
                'message' => 'Appointments can only be made with Blood Banks',
            ], 422);
        }

        // Check for conflicting appointments
        $existingAppointment = Appointment::where('donor_id', $validated['donor_id'])
            ->whereDate('appointment_date', $validated['appointment_date'])
            ->whereIn('status', ['Scheduled', 'Confirmed'])
            ->first();

        if ($existingAppointment) {
            return response()->json([
                'message' => 'You already have an appointment scheduled for this date',
            ], 422);
        }

        $appointment = Appointment::create($validated);

        // If this appointment is in response to a blood request, mark UserRequest as Responded
        if (!empty($validated['user_request_id'])) {
            $userRequest = \App\Models\UserRequest::find($validated['user_request_id']);
            if ($userRequest && $userRequest->user_id === $user->id) {
                $userRequest->markAsResponded();
            }
        }

        return response()->json([
            'message' => 'Appointment scheduled successfully',
            'appointment' => $appointment->load(['donor', 'organization']),
        ], 201);
    }

    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'appointment' => $appointment->load(['donor', 'organization']),
        ]);
    }

    /**
     * Update the specified appointment.
     */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'appointment_time' => ['sometimes', 'date_format:H:i'],
            'status' => ['sometimes', 'in:Scheduled,Confirmed,Completed,Cancelled,No-Show'],
            'donation_type' => ['sometimes', 'in:Whole Blood,Plasma,Platelets,Double Red Cells'],
            'notes' => ['nullable', 'string'],
            'cancellation_reason' => ['nullable', 'string'],
        ]);

        // Require cancellation reason for rejections
        if (isset($validated['status']) && $validated['status'] === 'Cancelled') {
            if (empty($validated['cancellation_reason'])) {
                return response()->json([
                    'message' => 'Cancellation reason is required when rejecting an appointment',
                ], 422);
            }
        }

        $user = $request->user();
        $oldStatus = $appointment->status;
        $newStatus = $validated['status'] ?? $oldStatus;

        // Track who made the change
        $validated['updated_by'] = $user->id;

        // Track confirmation
        if ($newStatus === 'Confirmed' && $oldStatus !== 'Confirmed') {
            $validated['accepted_by'] = $user->id;
            $validated['accepted_at'] = now();
            $action = 'confirmed';
            $message = 'Your blood donation appointment has been confirmed!';
        }

        // Track cancellation/rejection
        if ($newStatus === 'Cancelled' && $oldStatus !== 'Cancelled') {
            $validated['rejected_by'] = $user->id;
            $validated['rejected_at'] = now();
            $action = 'cancelled';
            $message = 'Your blood donation appointment has been cancelled.';
        }

        $appointment->update($validated);

        // Send notification to donor if status changed
        if (isset($action) && $appointment->donor && $appointment->donor->user) {
            try {
                $appointment->donor->user->notify(
                    new \App\Notifications\AppointmentNotification($appointment, $action, $message)
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send appointment notification: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment->fresh()->load(['donor.user', 'organization']),
        ]);
    }

    /**
     * Cancel an appointment.
     */
    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        if (!$appointment->canBeCancelled()) {
            return response()->json([
                'message' => 'This appointment cannot be cancelled',
            ], 422);
        }

        $appointment->update([
            'status' => 'Cancelled',
            'cancellation_reason' => $request->cancellation_reason ?? 'Cancelled by user',
        ]);

        return response()->json([
            'message' => 'Appointment cancelled successfully',
        ]);
    }

    /**
     * Get upcoming appointments for the authenticated donor.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->donor) {
            return response()->json([
                'message' => 'User is not a donor',
            ], 403);
        }

        $appointments = Appointment::where('donor_id', $user->donor->id)
            ->upcoming()
            ->with('organization')
            ->get();

        return response()->json([
            'appointments' => $appointments,
        ]);
    }

    /**
     * Get available time slots for a blood bank on a specific date.
     */
    public function availableSlots(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $date = Carbon::parse($validated['date']);

        // Get existing appointments for this date
        $bookedSlots = Appointment::where('organization_id', $organization->id)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['Scheduled', 'Confirmed'])
            ->pluck('appointment_time')
            ->map(fn($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        // Generate available slots (9 AM to 5 PM, every 30 minutes)
        $slots = [];
        $startHour = 9;
        $endHour = 17;
        $slotsPerHour = 3; // Max 3 appointments per time slot

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            foreach (['00', '30'] as $minute) {
                $time = sprintf('%02d:%s', $hour, $minute);
                $bookedCount = array_count_values($bookedSlots)[$time] ?? 0;

                if ($bookedCount < $slotsPerHour) {
                    $slots[] = [
                        'time' => $time,
                        'available' => $slotsPerHour - $bookedCount,
                    ];
                }
            }
        }

        return response()->json([
            'date' => $date->toDateString(),
            'organization' => $organization->only(['id', 'name']),
            'slots' => $slots,
        ]);
    }
}
