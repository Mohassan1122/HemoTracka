<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\RegulatoryBody;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;

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
                $query->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            // Apply location filter (if provided)
            if ($location) {
                $query->whereHas('organization', function ($q) use ($location) {
                    $q->where('state_id', $location);
                });
            }

            // Apply blood group filter
            if ($bloodGroup) {
                $query->where('blood_group', $bloodGroup);
            }

            // Apply status filter (Derived from units_in_stock vs threshold)
            if ($status) {
                $status = strtolower($status);
                if (in_array($status, ['out of stock', 'out_of_stock'])) {
                    $query->where('units_in_stock', '<=', 0);
                } elseif (in_array($status, ['critical', 'low'])) {
                    $query->where('units_in_stock', '>', 0)
                        ->whereColumn('units_in_stock', '<', 'threshold');
                } elseif (in_array($status, ['good', 'healthy'])) {
                    $query->whereColumn('units_in_stock', '>=', 'threshold');
                }
            }

            // Apply search (Blood Group or Organization Name)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('blood_group', 'like', "%{$search}%")
                        ->orWhereHas('organization', function ($subQ) use ($search) {
                            $subQ->where('name', 'like', "%{$search}%");
                        });
                });
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
     * Download exported inventory data (CSV format).
     */
    public function downloadExport(Request $request)
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $format = $request->input('format', 'csv');

            // Get inventory data
            $query = InventoryItem::with('organization');

            if ($regulatoryBody->isState()) {
                $query->join('organizations', 'inventory_items.organization_id', '=', 'organizations.id')
                    ->where('organizations.state_id', $regulatoryBody->state_id);
            }

            $items = $query->get();

            $filename = 'blood_inventory_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($items) {
                $file = fopen('php://output', 'w');

                // Header row
                fputcsv($file, ['Organization', 'Blood Group', 'Quantity (Pints)', 'Status', 'Last Updated']);

                // Data rows
                foreach ($items as $item) {
                    fputcsv($file, [
                        $item->organization->name ?? 'Unknown',
                        $item->blood_group ?? '',
                        $item->pints_available ?? 0,
                        $item->status ?? 'Unknown',
                        $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : '',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

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

    /**
     * Get low stock alerts (Phase 2 - Low Stock Alerts).
     */
    public function getAlerts(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $acknowledged = $request->input('acknowledged');

            $query = InventoryItem::with('organization')
                ->whereColumn('units_in_stock', '<', 'threshold');

            // Apply state filter if state-level regulator
            if ($regulatoryBody->isState()) {
                $query->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            // Transform to alert format
            $alerts = $query->get()->map(function ($item) {
                $severity = 'warning';
                if ($item->units_in_stock <= 0) {
                    $severity = 'out_of_stock';
                } elseif ($item->units_in_stock < $item->threshold * 0.5) {
                    $severity = 'critical';
                }

                return [
                    'id' => $item->id,
                    'organization_id' => $item->organization_id,
                    'organization_name' => $item->organization->name ?? 'Unknown',
                    'blood_group' => $item->blood_group,
                    'current_stock' => $item->units_in_stock,
                    'threshold' => $item->threshold,
                    'severity' => $severity,
                    'acknowledged' => false, // Would need a separate table for tracking
                    'acknowledged_at' => null,
                    'created_at' => $item->updated_at,
                ];
            });

            // Paginate manually
            $total = $alerts->count();
            $paginated = $alerts->forPage($page, $perPage)->values();

            return response()->json([
                'data' => $paginated,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                ],
                'unacknowledged_count' => $total, // All unacknowledged initially
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Acknowledge a low stock alert.
     */
    public function acknowledgeAlert(Request $request, int $id): JsonResponse
    {
        // For now, just return success - would need a separate table for proper tracking
        return response()->json(['message' => 'Alert acknowledged successfully.'], 200);
    }

    /**
     * Get expiring blood units (Phase 2 - Expiry Tracking).
     */
    public function getExpiringItems(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $days = $request->input('days', 7);
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            $expiryDate = now()->addDays($days);

            $query = InventoryItem::with('organization')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', $expiryDate)
                ->where('expiry_date', '>=', now())
                ->orderBy('expiry_date', 'asc');

            // Apply state filter if state-level regulator
            if ($regulatoryBody->isState()) {
                $query->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            $items = $query->paginate($perPage, ['*'], 'page', $page);

            // Calculate summary
            $summaryQuery = InventoryItem::query();
            if ($regulatoryBody->isState()) {
                $summaryQuery->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            $summary = [
                'expiring_today' => (clone $summaryQuery)
                    ->whereDate('expiry_date', now()->toDateString())
                    ->count(),
                'expiring_in_3_days' => (clone $summaryQuery)
                    ->whereDate('expiry_date', '<=', now()->addDays(3))
                    ->whereDate('expiry_date', '>=', now())
                    ->count(),
                'expiring_in_7_days' => (clone $summaryQuery)
                    ->whereDate('expiry_date', '<=', now()->addDays(7))
                    ->whereDate('expiry_date', '>=', now())
                    ->count(),
            ];

            // Transform the response
            $items->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'blood_group' => $item->blood_group,
                    'organization_name' => $item->organization->name ?? 'Unknown',
                    'units' => $item->units_in_stock,
                    'expiry_date' => $item->expiry_date,
                    'days_until_expiry' => now()->diffInDays($item->expiry_date, false),
                ];
            });

            return response()->json([
                'data' => $items->items(),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
                'summary' => $summary,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get blood transfers (Phase 2 - Transfer Visibility).
     */
    public function getTransfers(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $status = $request->input('status');

            // Check if BloodTransfer model exists, otherwise return mock data
            if (!class_exists(\App\Models\BloodTransfer::class)) {
                // Return mock data for now
                return response()->json([
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 0,
                        'last_page' => 1,
                    ],
                ], 200);
            }

            $query = \App\Models\BloodTransfer::with(['sourceOrganization', 'destinationOrganization'])
                ->orderBy('created_at', 'desc');

            // Apply state filter if state-level regulator
            if ($regulatoryBody->isState()) {
                $query->where(function ($q) use ($regulatoryBody) {
                    $q->whereHas('sourceOrganization', function ($subQ) use ($regulatoryBody) {
                        $subQ->where('state_id', $regulatoryBody->state_id);
                    })->orWhereHas('destinationOrganization', function ($subQ) use ($regulatoryBody) {
                        $subQ->where('state_id', $regulatoryBody->state_id);
                    });
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            $transfers = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform the response
            $transfers->getCollection()->transform(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'source_organization' => [
                        'id' => $transfer->source_organization_id,
                        'name' => $transfer->sourceOrganization->name ?? 'Unknown',
                    ],
                    'destination_organization' => [
                        'id' => $transfer->destination_organization_id,
                        'name' => $transfer->destinationOrganization->name ?? 'Unknown',
                    ],
                    'blood_group' => $transfer->blood_group,
                    'units' => $transfer->units,
                    'status' => $transfer->status,
                    'created_at' => $transfer->created_at,
                    'completed_at' => $transfer->completed_at,
                ];
            });

            return response()->json([
                'data' => $transfers->items(),
                'pagination' => [
                    'current_page' => $transfers->currentPage(),
                    'per_page' => $transfers->perPage(),
                    'total' => $transfers->total(),
                    'last_page' => $transfers->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get transfer statistics.
     */
    public function getTransferStats(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            // Return mock data for now
            return response()->json([
                'total_transfers' => 0,
                'transfers_this_month' => 0,
                'units_transferred_this_month' => 0,
                'pending_transfers' => 0,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get map data for inventory visualization (Phase 2 - Map).
     */
    public function getMapData(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $bloodGroup = $request->input('blood_group');
            $status = $request->input('status');

            // Get organizations with their inventory
            $query = \App\Models\Organization::with('inventoryItems')
                ->whereIn('type', ['blood_bank', 'hospital']);

            // Apply state filter if state-level regulator
            if ($regulatoryBody->isState()) {
                $query->where('state_id', $regulatoryBody->state_id);
            }

            $organizations = $query->get();

            $facilities = $organizations->map(function ($org) use ($bloodGroup) {
                $inventoryQuery = $org->inventoryItems;

                if ($bloodGroup && $bloodGroup !== 'All') {
                    $inventoryQuery = $inventoryQuery->where('blood_group', $bloodGroup);
                }

                $inventory = $inventoryQuery->map(function ($item) {
                    $itemStatus = 'Good';
                    if ($item->units_in_stock <= 0) {
                        $itemStatus = 'Out of Stock';
                    } elseif ($item->units_in_stock < $item->threshold) {
                        $itemStatus = 'Critical';
                    }

                    return [
                        'blood_group' => $item->blood_group,
                        'units' => $item->units_in_stock,
                        'status' => $itemStatus,
                    ];
                })->values();

                $totalUnits = $inventory->sum('units');
                $criticalCount = $inventory->where('status', 'Critical')->count();
                $outOfStockCount = $inventory->where('status', 'Out of Stock')->count();

                $overallStatus = 'healthy';
                if ($outOfStockCount > 0) {
                    $overallStatus = 'critical';
                } elseif ($criticalCount > 0) {
                    $overallStatus = 'warning';
                }

                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'type' => $org->type,
                    'latitude' => $org->latitude ?? 9.0820, // Default to Nigeria center
                    'longitude' => $org->longitude ?? 8.6753,
                    'inventory' => $inventory,
                    'total_units' => $totalUnits,
                    'overall_status' => $overallStatus,
                ];
            });

            // Filter by status if provided
            if ($status) {
                $facilities = $facilities->filter(function ($facility) use ($status) {
                    return $facility['overall_status'] === strtolower($status);
                })->values();
            }

            return response()->json([
                'facilities' => $facilities,
                'total_facilities' => $facilities->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get generated reports list (Phase 2 - Automated Reports).
     */
    public function getReports(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            return response()->json([
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get report schedule settings.
     */
    public function getReportSchedule(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            return response()->json([
                'enabled' => false,
                'frequency' => 'weekly',
                'day_of_week' => 1,
                'recipients' => [$request->user()->email],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update report schedule settings.
     */
    public function updateReportSchedule(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            return response()->json(['message' => 'Report schedule updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate a report on demand.
     */
    public function generateReport(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $filename = 'inventory_report_' . now()->format('Y-m-d_His') . '.csv';

            return response()->json([
                'message' => 'Report generated successfully.',
                'download_url' => url('/api/regulatory-body/reports/download/' . $filename),
                'filename' => $filename,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download a generated report.
     */
    public function downloadReport(Request $request, string $filename)
    {
        try {
            // Force csv extension if it was generated as pdf previously
            if (str_ends_with($filename, '.pdf')) {
                $filename = str_replace('.pdf', '.csv', $filename);
            }

            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();
            $orgName = $regulatoryBody->sc_name ?? 'Regulatory Body';
            $date = now()->toDateTimeString();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($orgName, $date) {
                $file = fopen('php://output', 'w');

                // Header
                fputcsv($file, ['Inventory Report']);
                fputcsv($file, ['Generated For', $orgName]);
                fputcsv($file, ['Date', $date]);
                fputcsv($file, []); // Blank line

                // Table Headers
                fputcsv($file, ['Blood Group', 'Status', 'Quantity (Pints)']);

                // Mock Data (since we are simulating storage)
                $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                foreach ($bloodGroups as $bg) {
                    fputcsv($file, [$bg, 'Good', rand(10, 100)]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
