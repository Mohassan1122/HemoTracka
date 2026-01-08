<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplianceRequest;
use App\Models\ComplianceMonitoring;
use App\Models\Donation;
use App\Models\BloodRequest;
use App\Models\RegulatoryBody;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegulatoryBodyComplianceController extends Controller
{
    /**
     * Get compliance status (PAGE 4 - Compliance Status).
     */
    public function getComplianceStatus(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $bloodType = $request->input('blood_type', '');
            $location = $request->input('location', '');
            $dateFrom = $request->input('date_from', null);
            $dateTo = $request->input('date_to', null);

            $query = ComplianceRequest::query();

            // Apply regulatory body filter
            if ($regulatoryBody->isState()) {
                $query->where('regulatory_body_id', $regulatoryBody->id);
            }

            // Apply date range filter
            if ($dateFrom) {
                $query->where('requested_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('requested_at', '<=', $dateTo);
            }

            $total = $query->count();
            $approved = (clone $query)->where('status', 'approved')->count();
            $pending = (clone $query)->where('status', 'pending')->count();
            $rejected = (clone $query)->where('status', 'rejected')->count();

            return response()->json([
                'total' => $total,
                'approved' => $approved,
                'pending' => $pending,
                'rejected' => $rejected,
                'compliance_distribution' => [
                    ['label' => 'Approved', 'value' => $approved],
                    ['label' => 'Pending', 'value' => $pending],
                    ['label' => 'Rejected', 'value' => $rejected],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get donation trends (PAGE 4 - Donation Trends).
     */
    public function getDonationTrends(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'quarterly');
            $year = $request->input('year', date('Y'));

            $donations = Donation::whereYear('created_at', $year)
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $chartData = $donations->map(function ($item) {
                return [
                    'period' => $item->month,
                    'count' => $item->count,
                ];
            });

            return response()->json(['chart_data' => $chartData], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get blood demand & supply (PAGE 4 - Blood Demand & Supply).
     */
    public function getBloodDemandSupply(Request $request): JsonResponse
    {
        try {
            $bloodType = $request->input('blood_type', '');
            $period = $request->input('period', 'monthly');

            $demandQuery = BloodRequest::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as date'),
                'blood_type',
                DB::raw('COUNT(*) as demand')
            )
            ->groupBy('date', 'blood_type')
            ->orderBy('date');

            if ($bloodType) {
                $demandQuery->where('blood_type', $bloodType);
            }

            $supplyQuery = DB::table('inventory_items')
                ->select(
                    DB::raw('DATE_FORMAT(last_restocked, "%Y-%m-%d") as date'),
                    'blood_type',
                    DB::raw('SUM(quantity) as supply')
                )
                ->groupBy('date', 'blood_type')
                ->orderBy('date');

            if ($bloodType) {
                $supplyQuery->where('blood_type', $bloodType);
            }

            $demand = $demandQuery->get();
            $supply = $supplyQuery->get();

            return response()->json([
                'chart_data' => [
                    'demand' => $demand,
                    'supply' => $supply,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get location filters (PAGE 4 - Filter Locations).
     */
    public function getFilterLocations(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $locationsQuery = State::select('id', 'name');

            // If state-level regulator, only show their state
            if ($regulatoryBody->isState()) {
                $locationsQuery->where('id', $regulatoryBody->state_id);
            }

            $locations = $locationsQuery->get();

            return response()->json(['locations' => $locations], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get blood type filters (PAGE 4 - Filter Blood Types).
     */
    public function getFilterBloodTypes(): JsonResponse
    {
        try {
            $bloodTypes = [
                ['id' => 'O-', 'type' => 'O', 'rh' => '-'],
                ['id' => 'O+', 'type' => 'O', 'rh' => '+'],
                ['id' => 'A-', 'type' => 'A', 'rh' => '-'],
                ['id' => 'A+', 'type' => 'A', 'rh' => '+'],
                ['id' => 'B-', 'type' => 'B', 'rh' => '-'],
                ['id' => 'B+', 'type' => 'B', 'rh' => '+'],
                ['id' => 'AB-', 'type' => 'AB', 'rh' => '-'],
                ['id' => 'AB+', 'type' => 'AB', 'rh' => '+'],
            ];

            return response()->json(['blood_types' => $bloodTypes], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
