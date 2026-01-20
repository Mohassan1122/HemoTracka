<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\RegulatoryBody;
use App\Models\BloodRequest;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegulatoryBodyBloodBanksController extends Controller
{
    /**
     * Get list of blood banks (PAGE 5 - Blood Banks Directory).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            $status = $request->input('status', '');
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $query = Organization::where('type', 'blood_bank')
                ->select(
                    'id',
                    'name',
                    'created_at as registration_date',
                    DB::raw('10000 as capacity'),
                    'status'
                );

            // Apply state filter if state-level regulator
            if ($regulatoryBody->isState()) {
                $query->where('state_id', $regulatoryBody->state_id);
            }

            // Apply search
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            // Apply status filter
            if ($status) {
                $query->where('status', $status);
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

            // Get paginated results
            $bloodBanks = $query->paginate($perPage, ['*'], 'page', $page);

            // Add additional metrics
            $bloodBanks->getCollection()->transform(function ($bank) {
                $bank->requests_count = BloodRequest::where('organization_id', $bank->id)->count();
                $bank->donors_count = Donation::where('organization_id', $bank->id)
                    ->distinct('donor_id')
                    ->count();
                return $bank;
            });

            return response()->json([
                'data' => $bloodBanks->items(),
                'pagination' => [
                    'current_page' => $bloodBanks->currentPage(),
                    'per_page' => $bloodBanks->perPage(),
                    'total' => $bloodBanks->total(),
                    'last_page' => $bloodBanks->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get blood bank details (PAGE 6 - Blood Bank Details).
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $query = Organization::where('id', $id)->where('type', 'blood_bank');

            // Apply state filter if state-level regulator
            if ($regulatoryBody->isState()) {
                $query->where('state_id', $regulatoryBody->state_id);
            }

            $bloodBank = $query->with(['state'])->first();

            if (!$bloodBank) {
                return response()->json(['error' => 'Blood bank not found.'], 404);
            }

            // Get inventory
            $inventory = DB::table('inventory_items')
                ->where('organization_id', $id)
                ->select('blood_group', DB::raw('SUM(units_in_stock) as pints_available'), DB::raw('MAX(updated_at) as last_restocked'))
                ->groupBy('blood_group')
                ->get();

            // Get donations count
            $totalDonations = Donation::where('organization_id', $id)->count();

            // Get average donor age
            $averageDonorAge = Donation::where('organization_id', $id)
                ->join('users', 'donations.donor_id', '=', 'users.id')
                ->selectRaw('AVG(YEAR(NOW()) - YEAR(users.date_of_birth)) as avg_age')
                ->value('avg_age');

            // Get recent donors
            $recentDonors = Donation::where('organization_id', $id)
                ->with('donor')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            // Get recent requests
            $recentRequests = BloodRequest::where('organization_id', $id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            return response()->json([
                'blood_bank' => [
                    'id' => $bloodBank->id,
                    'name' => $bloodBank->name,
                    'status' => $bloodBank->status,
                    'contact' => $bloodBank->contact_info,
                    'address' => $bloodBank->address,
                    'state' => $bloodBank->state,
                ],
                'inventory' => $inventory,
                'total_donations' => $totalDonations,
                'average_donor_age' => (int) $averageDonorAge,
                'recent_donors' => $recentDonors,
                'recent_requests' => $recentRequests,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory chart for blood bank (PAGE 6 - Inventory Chart).
     */
    public function getInventoryChart(Request $request, $id): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            // Verify blood bank exists and belongs to regulator's scope
            $bloodBank = Organization::where('id', $id)->where('type', 'blood_bank');

            if ($regulatoryBody->isState()) {
                $bloodBank->where('state_id', $regulatoryBody->state_id);
            }

            if (!$bloodBank->exists()) {
                return response()->json(['error' => 'Blood bank not found.'], 404);
            }

            $period = $request->input('period', 'monthly');

            $chartData = DB::table('inventory_items')
                ->where('organization_id', $id)
                ->select(
                    DB::raw('DATE_FORMAT(updated_at, "%Y-%m-%d") as date'),
                    'blood_group',
                    DB::raw('SUM(units_in_stock) as quantity')
                )
                ->groupBy('date', 'blood_group')
                ->orderBy('date')
                ->get();

            return response()->json(['chart_data' => $chartData], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get blood demand & supply for blood bank (PAGE 6 - Blood Demand & Supply).
     */
    public function getBloodDemandSupply(Request $request, $id): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            // Get demand (blood requests)
            $demandData = BloodRequest::where('organization_id', $id)
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as date'),
                    'blood_group',
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date', 'blood_group')
                ->orderBy('date')
                ->get();

            // Get supply (inventory)
            $supplyData = DB::table('inventory_items')
                ->where('organization_id', $id)
                ->select(
                    DB::raw('DATE_FORMAT(updated_at, "%Y-%m-%d") as date'),
                    'blood_group',
                    DB::raw('SUM(units_in_stock) as quantity')
                )
                ->groupBy('date', 'blood_group')
                ->orderBy('date')
                ->get();

            return response()->json([
                'demand' => $demandData,
                'supply' => $supplyData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get filter statuses.
     */
    public function getFilterStatuses(): JsonResponse
    {
        return response()->json([
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'suspended', 'label' => 'Suspended'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ], 200);
    }
}
