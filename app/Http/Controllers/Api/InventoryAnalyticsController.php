<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryAnalyticsController extends Controller
{
    /**
     * Helper to get organization ID
     */
    private function getOrganizationId($user)
    {
        if (isset($user->organization_id) && $user->organization_id) {
            return $user->organization_id;
        }

        if (get_class($user) === 'App\Models\Organization') {
            return $user->id;
        }

        if ($user->linkedOrganization) {
            return $user->linkedOrganization->id;
        }

        return null;
    }

    /**
     * Get inventory summary statistics
     */
    public function summary(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json([
                'total_units' => 0,
                'total_items' => 0,
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
                'expiring_soon_count' => 0,
            ]);
        }

        $totalUnits = InventoryItem::where('organization_id', $orgId)->sum('units_in_stock');
        $totalItems = InventoryItem::where('organization_id', $orgId)->count();
        $lowStockCount = InventoryItem::where('organization_id', $orgId)
            ->whereColumn('units_in_stock', '<=', 'threshold')
            ->where('units_in_stock', '>', 0)
            ->count();
        $outOfStockCount = InventoryItem::where('organization_id', $orgId)
            ->where('units_in_stock', 0)
            ->count();
        $expiringCount = InventoryItem::where('organization_id', $orgId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays(14))
            ->where('expiry_date', '>', Carbon::now())
            ->count();

        return response()->json([
            'total_units' => $totalUnits,
            'total_items' => $totalItems,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'expiring_soon_count' => $expiringCount,
        ]);
    }

    /**
     * Get inventory breakdown by blood group
     */
    public function byBloodGroup(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['data' => []]);
        }

        $data = InventoryItem::where('organization_id', $orgId)
            ->select('blood_group', DB::raw('SUM(units_in_stock) as total_units'))
            ->groupBy('blood_group')
            ->orderBy('blood_group')
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * Get inventory breakdown by type (Whole Blood, Platelets, etc.)
     */
    public function byType(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['data' => []]);
        }

        $data = InventoryItem::where('organization_id', $orgId)
            ->select('type', DB::raw('SUM(units_in_stock) as total_units'))
            ->groupBy('type')
            ->orderBy('type')
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * Get expiry timeline for inventory items
     */
    public function expiryTimeline(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json([
                'expired' => 0,
                'within_7_days' => 0,
                'within_14_days' => 0,
                'within_30_days' => 0,
                'beyond_30_days' => 0,
                'no_expiry' => 0,
            ]);
        }

        $expired = InventoryItem::where('organization_id', $orgId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::now())
            ->sum('units_in_stock');

        $within7Days = InventoryItem::where('organization_id', $orgId)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(7)])
            ->sum('units_in_stock');

        $within14Days = InventoryItem::where('organization_id', $orgId)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [Carbon::now()->addDays(7), Carbon::now()->addDays(14)])
            ->sum('units_in_stock');

        $within30Days = InventoryItem::where('organization_id', $orgId)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [Carbon::now()->addDays(14), Carbon::now()->addDays(30)])
            ->sum('units_in_stock');

        $beyond30Days = InventoryItem::where('organization_id', $orgId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', Carbon::now()->addDays(30))
            ->sum('units_in_stock');

        $noExpiry = InventoryItem::where('organization_id', $orgId)
            ->whereNull('expiry_date')
            ->sum('units_in_stock');

        return response()->json([
            'expired' => $expired,
            'within_7_days' => $within7Days,
            'within_14_days' => $within14Days,
            'within_30_days' => $within30Days,
            'beyond_30_days' => $beyond30Days,
            'no_expiry' => $noExpiry,
        ]);
    }

    /**
     * Get alerts summary
     */
    public function alertsSummary(Request $request): JsonResponse
    {
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json([
                'critical' => 0,
                'warnings' => 0,
                'info' => 0,
                'total' => 0,
            ]);
        }

        $critical = InventoryAlert::where('organization_id', $orgId)
            ->where('severity', 'critical')
            ->unread()
            ->count();

        $warnings = InventoryAlert::where('organization_id', $orgId)
            ->where('severity', 'warning')
            ->unread()
            ->count();

        $info = InventoryAlert::where('organization_id', $orgId)
            ->where('severity', 'info')
            ->unread()
            ->count();

        return response()->json([
            'critical' => $critical,
            'warnings' => $warnings,
            'info' => $info,
            'total' => $critical + $warnings + $info,
        ]);
    }

    /**
     * Get stock levels history (mock data for now - would require transaction logging)
     */
    public function stockHistory(Request $request): JsonResponse
    {
        // This would normally come from a stock_transactions table
        // For now, return current state as a single data point
        $orgId = $this->getOrganizationId($request->user());

        if (!$orgId) {
            return response()->json(['data' => []]);
        }

        $currentStock = InventoryItem::where('organization_id', $orgId)
            ->select('blood_group', DB::raw('SUM(units_in_stock) as units'))
            ->groupBy('blood_group')
            ->get()
            ->keyBy('blood_group');

        $history = [];
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        // Generate mock 7-day history
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $entry = ['date' => $date];

            foreach ($bloodGroups as $bg) {
                // Add some variance for visualization
                $base = $currentStock[$bg]->units ?? 0;
                $variance = rand(-5, 5);
                $entry[$bg] = max(0, $base + $variance);
            }

            $history[] = $entry;
        }

        return response()->json(['data' => $history]);
    }
}
