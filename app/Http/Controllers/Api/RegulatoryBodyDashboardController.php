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
            $totalBloodBanks = $organizationsQuery->where('type', 'blood_bank')->count();

            // Get health facilities
            $totalHealthFacilities = $organizationsQuery->where('type', 'health_facility')->count();

            // Get total donors
            $totalDonors = Donor::count();

            // Get blood in stock
            $bloodInStock = InventoryItem::sum('units_in_stock');

            // Get compliance stats
            $complianceRequests = ComplianceRequest::query();
            if ($regulatoryBody->isFederal()) {
                $complianceRequests = ComplianceRequest::all();
            } else {
                $complianceRequests = $complianceRequests->whereHas('regulatoryBody', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            $approvedRequests = $complianceRequests->where('status', 'approved')->count();
            $pendingRequests = $complianceRequests->where('status', 'pending')->count();
            $rejectedRequests = $complianceRequests->where('status', 'rejected')->count();

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
            $period = $request->input('period', 'monthly'); // weekly, monthly, yearly

            $query = InventoryItem::select(
                DB::raw('DATE_FORMAT(updated_at, "%Y-%m-%d") as date'),
                'blood_group',
                DB::raw('SUM(units_in_stock) as units_in_stock')
            )
                ->groupBy('date', 'blood_group')
                ->orderBy('date');

            // Filter by period
            $query = $this->applyPeriodFilter($query, $period, 'updated_at');

            $chartData = $query->get();

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

            $donations = Donation::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
                ->groupBy('month')
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
     * Apply period filter to query.
     */
    private function applyPeriodFilter($query, $period, $dateColumn)
    {
        switch ($period) {
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
