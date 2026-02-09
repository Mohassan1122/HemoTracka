<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplianceRequest;
use App\Models\ComplianceMonitoring;
use App\Models\Donation;
use App\Models\BloodRequest;
use App\Models\RegulatoryBody;
use App\Models\State;
use App\Models\Organization;
use App\Notifications\ComplianceStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

            // Apply regulatory body filter for state-level
            // Apply regulatory body filter for state-level
            if ($regulatoryBody->isState()) {
                $query->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            // Apply date range filter
            if ($dateFrom) {
                $query->where('submission_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('submission_date', '<=', $dateTo);
            }

            $total = $query->count();
            $approved = (clone $query)->where('status', 'Approved')->count();
            $pending = (clone $query)->where('status', 'Pending')->count();
            $rejected = (clone $query)->where('status', 'Rejected')->count();

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
                'blood_group',
                DB::raw('COUNT(*) as demand')
            )
                ->groupBy('date', 'blood_group')
                ->orderBy('date');

            if ($bloodType) {
                $demandQuery->where('blood_group', $bloodType);
            }

            $supplyQuery = DB::table('inventory_items')
                ->select(
                    DB::raw('DATE_FORMAT(updated_at, "%Y-%m-%d") as date'),
                    'blood_group',
                    DB::raw('SUM(units_in_stock) as supply')
                )
                ->groupBy('date', 'blood_group')
                ->orderBy('date');

            if ($bloodType) {
                $supplyQuery->where('blood_group', $bloodType);
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

    /**
     * Get list of compliance requests (PAGE 4 - Compliance Requests List).
     */
    public function getComplianceRequests(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $perPage = (int) $request->input('per_page', 10);
            $status = $request->input('status', '');
            $search = $request->input('search', '');

            $query = ComplianceRequest::with(['organization', 'reviewedBy']);

            Log::info('getComplianceRequests called', [
                'user_id' => $request->user()->id,
                'is_state' => $regulatoryBody->isState(),
                'reg_body_id' => $regulatoryBody->id
            ]);

            // Apply regulatory body filter for state-level
            if ($regulatoryBody->isState()) {
                $query->whereHas('organization', function ($q) use ($regulatoryBody) {
                    $q->where('state_id', $regulatoryBody->state_id);
                });
            }

            // Apply status filter
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            // Apply search filter
            if ($search) {
                $query->whereHas('organization', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            Log::info('Compliance Query SQL', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);

            DB::enableQueryLog();
            $requests = $query->orderBy('created_at', 'desc')->paginate($perPage);
            Log::info('Compliance Query SQL', DB::getQueryLog());

            Log::info('Compliance Requests Count', ['count' => $requests->count(), 'total' => $requests->total()]);

            return response()->json([
                'data' => $requests->items(),
                'pagination' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get single compliance request details (PAGE 4 - Compliance Request Detail).
     */
    public function getComplianceRequest(Request $request, $id): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $complianceRequest = ComplianceRequest::with(['organization', 'reviewedBy', 'regulatoryBody'])
                ->findOrFail($id);

            // Verify access for state-level regulators
            if ($regulatoryBody->isState() && $complianceRequest->regulatory_body_id !== $regulatoryBody->id && $complianceRequest->regulatory_body_id !== null) {
                return response()->json(['error' => 'Unauthorized access.'], 403);
            }

            return response()->json([
                'compliance_request' => $complianceRequest,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve a compliance request (PAGE 4 - Approve Request).
     */
    public function approveRequest(Request $request, $id): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $complianceRequest = ComplianceRequest::with('organization')->findOrFail($id);

            // Verify access for state-level regulators
            if ($regulatoryBody->isState() && $complianceRequest->regulatory_body_id !== $regulatoryBody->id && $complianceRequest->regulatory_body_id !== null) {
                return response()->json(['error' => 'Unauthorized access.'], 403);
            }

            // Check if already processed
            if ($complianceRequest->status !== 'Pending') {
                return response()->json(['error' => 'This request has already been processed.'], 400);
            }

            $notes = $request->input('notes', null);

            // Approve the request using model method
            $complianceRequest->approve($request->user()->id, $notes);

            // Update organization to approved status if applicable
            if ($complianceRequest->organization) {
                $complianceRequest->organization->update(['is_approved' => true]);

                // Refresh the organization to ensure the updated value is loaded
                $complianceRequest->organization->fresh();

                // Send notification to organization's user
                if ($complianceRequest->organization->user) {
                    $complianceRequest->organization->user->notify(
                        new ComplianceStatusNotification($complianceRequest, 'approved')
                    );
                }
            }

            return response()->json([
                'message' => 'Compliance request approved successfully.',
                'compliance_request' => $complianceRequest->fresh(['organization', 'reviewedBy']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject a compliance request (PAGE 4 - Reject Request).
     */
    public function rejectRequest(Request $request, $id): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $complianceRequest = ComplianceRequest::with('organization')->findOrFail($id);

            // Verify access for state-level regulators
            if ($regulatoryBody->isState() && $complianceRequest->regulatory_body_id !== $regulatoryBody->id && $complianceRequest->regulatory_body_id !== null) {
                return response()->json(['error' => 'Unauthorized access.'], 403);
            }

            // Check if already processed
            if ($complianceRequest->status !== 'Pending') {
                return response()->json(['error' => 'This request has already been processed.'], 400);
            }

            $rejectionReason = $request->input('rejection_reason');
            $notes = $request->input('notes', null);

            if (!$rejectionReason) {
                return response()->json(['error' => 'Rejection reason is required.'], 422);
            }

            // Reject the request using model method
            $complianceRequest->reject($request->user()->id, $rejectionReason, $notes);

            // Send rejection email to organization
            if ($complianceRequest->organization && $complianceRequest->organization->email) {
                try {
                    Mail::send('emails.compliance-rejection', [
                        'organizationName' => $complianceRequest->organization->name,
                        'rejectionReason' => $rejectionReason,
                        'notes' => $notes,
                    ], function ($message) use ($complianceRequest) {
                        $message->to($complianceRequest->organization->email)
                            ->subject('Compliance Request Rejected - HemoTrackr');
                    });
                } catch (\Exception $emailError) {
                    // Log email error but don't fail the request
                    \Log::error('Failed to send rejection email: ' . $emailError->getMessage());
                }

                // Send notification to organization's user
                if ($complianceRequest->organization->user) {
                    $complianceRequest->organization->user->notify(
                        new ComplianceStatusNotification($complianceRequest, 'rejected', $rejectionReason)
                    );
                }
            }

            return response()->json([
                'message' => 'Compliance request rejected successfully.',
                'compliance_request' => $complianceRequest->fresh(['organization', 'reviewedBy']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

