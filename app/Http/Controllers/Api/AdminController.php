<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\User;
use App\Models\Rider;
use App\Models\Delivery;
use App\Models\Appointment;
use App\Models\Feedback;
use App\Notifications\OrganizationStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AdminController extends Controller
{
    /**
     * Platform Overview Dashboard.
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'active_donors' => Donor::where('status', 'Eligible')->count(),
            'total_hospitals' => Organization::where('type', 'Hospital')->count(),
            'total_blood_banks' => Organization::where('type', 'Blood Bank')->count(),
            'scheduled_appointments' => Appointment::where('status', 'Scheduled')->where('appointment_date', '>=', now())->count(),
            'total_blood_units' => InventoryItem::sum('units_in_stock'),
            'inventory_by_group' => InventoryItem::selectRaw('blood_group, SUM(units_in_stock) as units')
                ->groupBy('blood_group')
                ->get(),
            'recent_activity' => $this->getRecentActivity(),
        ];

        return response()->json($stats);
    }

    /**
     * List all organizations with advanced filtering.
     */
    public function organizations(Request $request): JsonResponse
    {
        $query = Organization::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $organizations = $query->withCount(['users', 'donors', 'bloodRequests'])
            ->paginate($request->get('per_page', 15));

        return response()->json($organizations);
    }

    /**
     * Approve or update organization status.
     */
    public function updateOrganizationStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Pending,Active,Suspended'],
        ]);

        $org = Organization::findOrFail($id);
        $org->update(['status' => $validated['status']]);

        // Notify organization users
        Notification::send($org->users, new OrganizationStatusNotification($org));

        return response()->json([
            'message' => "Organization status updated to {$validated['status']}",
            'organization' => $org
        ]);
    }

    /**
     * Global User Management.
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->with('organization')
            ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * List all donors across the platform.
     */
    public function donors(Request $request): JsonResponse
    {
        $query = Donor::query();

        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        $donors = $query->with('organization')
            ->withCount('donations')
            ->paginate($request->get('per_page', 15));

        return response()->json($donors);
    }

    /**
     * Rider and Logistics Oversight.
     */
    public function logistics(): JsonResponse
    {
        $stats = [
            'total_riders' => Rider::count(),
            'active_deliveries' => Delivery::whereIn('status', ['Order Taken', 'Preparing', 'Product Ready', 'En Route'])->count(),
            'completed_deliveries' => Delivery::where('status', 'Delivered')->count(),
            'rider_status_breakdown' => Rider::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Global Feedback Review.
     */
    public function feedback(): JsonResponse
    {
        $feedbacks = Feedback::with(['user', 'organization'])
            ->latest()
            ->paginate(15);

        return response()->json($feedbacks);
    }

    /**
     * Helper to get recent system activity.
     */
    private function getRecentActivity()
    {
        // Mocking an activity feed since we don't have a dedicated Activity model yet
        // In a real app, this would query an audit_logs table
        return [
            ['type' => 'registration', 'message' => 'New Blood Bank registered: PH Central Bank', 'time' => now()->subMinutes(15)],
            ['type' => 'appointment', 'message' => 'Donor Caleb scheduled an appointment', 'time' => now()->subHours(2)],
            ['type' => 'delivery', 'message' => 'Fast Rider delivered 10 units to BMH', 'time' => now()->subHours(5)],
        ];
    }
}
