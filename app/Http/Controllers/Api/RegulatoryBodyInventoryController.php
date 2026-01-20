<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\RegulatoryBody;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class RegulatoryBodyInventoryController extends Controller
{
    /**
     * Get inventory list (PAGE 7 - Inventory Management).
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
            $bloodGroup = $request->input('blood_group', '');
            $location = $request->input('location', '');
            $status = $request->input('status', '');
            $search = $request->input('search', '');

            $query = InventoryItem::with('organization')
                ->select('inventory_items.*');

            // Apply state filter if state-level regulator
            if ($regulatoryBody->isState()) {
                $query->join('organizations', 'inventory_items.organization_id', '=', 'organizations.id')
                    ->where('organizations.state_id', $regulatoryBody->state_id)
                    ->select('inventory_items.*');
            }

            // Apply blood group filter
            if ($bloodGroup) {
                $query->where('blood_group', $bloodGroup);
            }

            // Apply status filter
            if ($status) {
                $query->where('status', $status);
            }

            // Apply search
            if ($search) {
                $query->where('blood_group', 'like', '%' . $search . '%');
            }

            // Get paginated results
            $inventory = $query->paginate($perPage, ['*'], 'page', $page);

            // Format the response
            $inventory->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'bank_name' => $item->organization->name ?? 'N/A',
                    'blood_group' => $item->blood_group,
                    'pints_available' => $item->units_in_stock,
                    'updated_at' => $item->updated_at,
                    'usage_rate' => 'N/A', // Not tracked currently
                    'status' => $item->units_in_stock <= 0 ? 'Out of Stock' : ($item->units_in_stock < $item->threshold ? 'Critical' : 'Good'),
                ];
            });

            return response()->json([
                'data' => $inventory->items(),
                'pagination' => [
                    'current_page' => $inventory->currentPage(),
                    'per_page' => $inventory->perPage(),
                    'total' => $inventory->total(),
                    'last_page' => $inventory->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory chart (PAGE 7 - Inventory Stock Chart).
     */
    public function getChart(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $period = $request->input('period', 'monthly');

            $query = DB::table('inventory_items')
                ->select(
                    'blood_group',
                    DB::raw('SUM(units_in_stock) as total_quantity')
                )
                ->groupBy('blood_group')
                ->orderBy('blood_group');

            if ($regulatoryBody->isState()) {
                $query->join('organizations', 'inventory_items.organization_id', '=', 'organizations.id')
                    ->where('organizations.state_id', $regulatoryBody->state_id);
            }

            $chartData = $query->get();

            return response()->json(['chart_data' => $chartData], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export inventory (PAGE 7 - Export Functionality).
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $format = $request->input('format', 'csv'); // csv or pdf

            $query = InventoryItem::with('organization');

            if ($regulatoryBody->isState()) {
                $query->join('organizations', 'inventory_items.organization_id', '=', 'organizations.id')
                    ->where('organizations.state_id', $regulatoryBody->state_id);
            }

            $data = $query->get();

            if ($format === 'csv') {
                // Return CSV download
                $filename = 'blood_inventory_' . date('Y-m-d_H-i-s') . '.csv';
                return response()->json([
                    'message' => 'Export generated successfully.',
                    'download_url' => url('/api/regulatory-body/inventory/export/download?format=csv&token=' . $request->user()->currentAccessToken()->token),
                    'filename' => $filename,
                ], 200);
            } else {
                // Return PDF download
                $filename = 'blood_inventory_' . date('Y-m-d_H-i-s') . '.pdf';
                return response()->json([
                    'message' => 'Export generated successfully.',
                    'download_url' => url('/api/regulatory-body/inventory/export/download?format=pdf&token=' . $request->user()->currentAccessToken()->token),
                    'filename' => $filename,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory statistics (PAGE 7 - Inventory Stats).
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $query = InventoryItem::query();

            if ($regulatoryBody->isState()) {
                $query->join('organizations', 'inventory_items.organization_id', '=', 'organizations.id')
                    ->where('organizations.state_id', $regulatoryBody->state_id);
            }

            $totalPints = $query->sum('units_in_stock');
            $criticalItems = (clone $query)->whereColumn('units_in_stock', '<', 'threshold')->get();
            $outOfStock = (clone $query)->where('units_in_stock', '<=', 0)->get();

            return response()->json([
                'total_pints' => $totalPints,
                'critical_items_count' => $criticalItems->count(),
                'out_of_stock_count' => $outOfStock->count(),
                'critical_items' => $criticalItems,
                'out_of_stock_items' => $outOfStock,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
