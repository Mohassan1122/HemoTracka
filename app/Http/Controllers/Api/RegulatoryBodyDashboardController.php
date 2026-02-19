<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodBank;
use App\Models\ComplianceRequest;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\RegulatoryBody;
use App\Models\InventoryItem;
use App\Models\BloodRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegulatoryBodyDashboardController extends Controller
{
    /**
     * Get dashboard statistics (PAGE 3 - Dashboard Stats).
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            // Get organizations based on level
            $organizationsQuery = Organization::query();
            if ($regulatoryBody->isState()) {
                $organizationsQuery->where('state_id', $regulatoryBody->state_id);
            }

            // Get blood banks
            $totalBloodBanks = (clone $organizationsQuery)->where('type', 'Blood Bank')->count();

            // Get health facilities
            $totalHealthFacilities = (clone $organizationsQuery)->where('type', 'Hospital')->count();

            // Get total donors
            $totalDonors = Donor::count();

            // Get blood in stock
            $bloodInStock = InventoryItem::sum('units_in_stock');

            // Get compliance stats
            $complianceRequests = ComplianceRequest::query();
            if ($regulatoryBody->isFederal()) {
                $complianceRequests = ComplianceRequest::all();
            } else {
                $complianceRequests = $complianceRequests->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            $approvedRequests = $complianceRequests->where('status', 'approved')->count();
            $pendingRequests = $complianceRequests->where('status', 'pending')->count();
            $rejectedRequests = $complianceRequests->where('status', 'rejected')->count();

            // Get blood request stats
            $bloodRequestsQuery = BloodRequest::query();
            if ($regulatoryBody->isState()) {
                $bloodRequestsQuery->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            $breq_total = (clone $bloodRequestsQuery)->count();
            $breq_approved = (clone $bloodRequestsQuery)->where('status', 'approved')->count();
            $breq_pending = (clone $bloodRequestsQuery)->where('status', 'pending')->count();
            $breq_rejected = (clone $bloodRequestsQuery)->where('status', 'rejected')->count();

            return response()->json([
                'statistics' => [
                    'total_blood_banks' => $totalBloodBanks,
                    'total_health_facilities' => $totalHealthFacilities,
                    'total_donors' => $totalDonors,
                    'blood_in_stock' => $bloodInStock,
                ],
                'compliance' => [
                    'total' => $approvedRequests + $pendingRequests + $rejectedRequests,
                    'approved' => $approvedRequests,
                    'pending' => $pendingRequests,
                    'rejected' => $rejectedRequests,
                ],
                'blood_requests' => [
                    'total' => $breq_total,
                    'approved' => $breq_approved,
                    'pending' => $breq_pending,
                    'rejected' => $breq_rejected,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory chart data (PAGE 3 - Inventory Chart).
     */
    public function getInventoryChart(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'monthly');
            $bloodGroup = $request->input('blood_group', 'All');

            $query = InventoryItem::select(
                DB::raw('DATE_FORMAT(updated_at, "%M") as month'),
                DB::raw('SUM(units_in_stock) as units_in_stock'),
                DB::raw('MONTH(updated_at) as month_num')
            );

            if ($bloodGroup && $bloodGroup !== 'All') {
                $query->where('blood_group', $bloodGroup);
            }

            // Apply Period Filter
            $this->applyPeriodFilter($query, $period, 'updated_at');

            $chartData = $query->groupBy('month', 'month_num')
                ->orderBy('month_num')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'units_in_stock' => (int) $item->units_in_stock
                    ];
                });

            return response()->json(['chart_data' => $chartData], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get donation trends (PAGE 3 - Donation Trends).
     */
    public function getDonationTrends(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'quarterly');

            // Format date based on period granularity? For now keep monthly grouping but filter range.
            $query = Donation::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            );

            // Apply Period Filter
            $this->applyPeriodFilter($query, $period, 'created_at');

            $donations = $query->groupBy('month')
                ->orderBy('month')
                ->get();

            $trendData = [
                'data' => $donations,
                'total' => $donations->sum('count'),
            ];

            return response()->json($trendData, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent donors (PAGE 3 - Recent Donors).
     */
    public function getRecentDonors(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 5);

            $donors = Donor::select('id', 'first_name', 'last_name', 'blood_group', 'created_at')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            return response()->json(['donors' => $donors], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent blood requests (PAGE 3 - Recent Requests).
     */
    public function getRecentRequests(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 5);

            $requests = BloodRequest::select('id', 'blood_group', 'units_needed', 'status', 'created_at')
                ->with('organization:id,name')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            return response()->json(['requests' => $requests], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get donor demographics (PAGE 3 - Donor Age Pie Chart).
     */
    public function getDonorDemographics(Request $request): JsonResponse
    {
        try {
            $demographics = Donor::select(
                DB::raw("CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 46 THEN '46-60+'
                    ELSE 'Unknown'
                END as age_group"),
                DB::raw('COUNT(*) as count')
            )
                ->groupBy('age_group')
                ->get();

            return response()->json(['demographics' => $demographics], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get blood supply vs demand (PAGE 3 - Demand & Supply Chart).
     */
    public function getBloodSupplyDemand(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'monthly');

            // Donations Query
            $donationsQuery = Donation::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            );
            $this->applyPeriodFilter($donationsQuery, $period, 'created_at');
            $donations = $donationsQuery->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('count', 'month');

            // Blood Requests Query
            $requestsQuery = BloodRequest::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            );
            $this->applyPeriodFilter($requestsQuery, $period, 'created_at');
            $requests = $requestsQuery->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('count', 'month');

            // Merge keys
            $months = $donations->keys()->merge($requests->keys())->unique()->sort();

            $data = $months->map(function ($month) use ($donations, $requests) {
                return [
                    'month' => $month,
                    'supply' => $donations[$month] ?? 0,
                    'demand' => $requests[$month] ?? 0,
                ];
            })->values();

            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory expiry stats (PAGE 3 - Expires Cards).
     */
    public function getInventoryExpiry(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            // Scope query based on regulatory body state if applicable
            $inventoryQuery = InventoryItem::query();
            if ($regulatoryBody && $regulatoryBody->isState()) {
                $inventoryQuery->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            $today = $inventoryQuery->clone()
                ->where('expiry_date', Carbon::today())
                ->sum('units_in_stock');

            $next7Days = $inventoryQuery->clone()
                ->whereBetween('expiry_date', [Carbon::tomorrow(), Carbon::today()->addDays(7)])
                ->sum('units_in_stock');

            $next30Days = $inventoryQuery->clone()
                ->whereBetween('expiry_date', [Carbon::today()->addDays(8), Carbon::today()->addDays(30)])
                ->sum('units_in_stock');

            return response()->json([
                'expires_today' => $today,
                'expires_in_7_days' => $next7Days,
                'expires_in_30_days' => $next30Days
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get highest donors (PAGE 3 - Highest Donors).
     */
    public function getHighestDonors(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 5);

            $donors = Donor::withCount('donations')
                ->orderByDesc('donations_count')
                ->limit($limit)
                ->get()
                ->map(function ($donor) {
                    return [
                        'id' => $donor->id,
                        'name' => $donor->first_name . ' ' . $donor->last_name,
                        'blood_group' => $donor->blood_group,
                        'donation_count' => $donor->donations_count,
                        'last_donation' => $donor->last_donation_date,
                    ];
                });

            return response()->json(['donors' => $donors], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get blood inventory map data (PAGE 3 - Map).
     */
    public function getBloodInventoryMapData(Request $request): JsonResponse
    {
        try {
            $bloodGroup = $request->input('blood_group', 'All');

            $query = InventoryItem::select('states.name', DB::raw('SUM(inventory_items.units_in_stock) as value'))
                ->join('organizations', 'inventory_items.organization_id', '=', 'organizations.id')
                ->join('states', 'organizations.state_id', '=', 'states.id');

            if ($bloodGroup && $bloodGroup !== 'All') {
                $query->where('inventory_items.blood_group', $bloodGroup);
            }

            $data = $query->groupBy('states.name')->get();

            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply period filter to query.
     */
    private function applyPeriodFilter($query, $period, $dateColumn)
    {
        switch ($period) {
            case 'Last 7 days':
                return $query->where($dateColumn, '>=', now()->subDays(7));
            case 'Last 30 days':
                return $query->where($dateColumn, '>=', now()->subDays(30));
            case 'Last 90 days':
                return $query->where($dateColumn, '>=', now()->subDays(90));
            case 'weekly':
                return $query->where($dateColumn, '>=', now()->subWeeks(4));
            case 'monthly':
                return $query->where($dateColumn, '>=', now()->subMonths(12));
            case 'yearly':
                return $query->where($dateColumn, '>=', now()->subYears(5));
            default:
                return $query;
        }
    }
}
