<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\InventoryItem;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $user->organization_id;

        $stats = [
            'total_donors' => Donor::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))->count(),
            'total_donations' => Donation::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))->count(),
            'total_units_donated' => Donation::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))->sum('units'),
            'pending_requests' => BloodRequest::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))->where('status', 'Pending')->count(),
            'completed_requests' => BloodRequest::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))->where('status', 'Completed')->count(),
            'total_inventory_units' => InventoryItem::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))->sum('units_in_stock'),
        ];

        return response()->json($stats);
    }

    /**
     * Get donation statistics by blood group.
     */
    public function donationsByBloodGroup(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        $data = Donation::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))
            ->selectRaw('blood_group, SUM(units) as total_units, COUNT(*) as total_donations')
            ->groupBy('blood_group')
            ->get();

        return response()->json($data);
    }

    /**
     * Get inventory statistics by blood group.
     */
    public function inventoryByBloodGroup(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        $data = InventoryItem::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))
            ->selectRaw('blood_group, SUM(units_in_stock) as total_units')
            ->groupBy('blood_group')
            ->get();

        return response()->json($data);
    }

    /**
     * Get request statistics by status.
     */
    public function requestsByStatus(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        $data = BloodRequest::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return response()->json($data);
    }

    /**
     * Get monthly donation trends.
     */
    public function monthlyDonations(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id;
        $year = $request->get('year', date('Y'));

        $data = Donation::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))
            ->whereYear('donation_date', $year)
            ->selectRaw('MONTH(donation_date) as month, SUM(units) as total_units, COUNT(*) as total_donations')
            ->groupBy(DB::raw('MONTH(donation_date)'))
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    /**
     * Get low stock alerts.
     */
    public function lowStockAlerts(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        $items = InventoryItem::when($organizationId, fn($q) => $q->where('organization_id', $organizationId))
            ->whereColumn('units_in_stock', '<=', 'threshold')
            ->with('organization')
            ->get();

        return response()->json($items);
    }
}
