<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\BloodRequest;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ActivityFeedController extends Controller
{
    /**
     * Get an aggregated activity feed for the mobile dashboard.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $orgId = $user->organization_id;

        // 1. Get recent donations (if applicable to this user's role/org)
        $donations = Donation::with('donor.user')
            ->whereHas('donor', function ($q) use ($orgId) {
                if ($orgId)
                    $q->where('organization_id', $orgId);
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($d) {
                $donorName = $d->donor && $d->donor->user ? $d->donor->user->full_name : 'Anonymous Donor';
                return [
                    'type' => 'Donation',
                    'title' => 'New Donation from ' . $donorName,
                    'subtitle' => $d->blood_group . ' - ' . $d->units . ' units',
                    'time' => $d->created_at->diffForHumans(),
                    'timestamp' => $d->created_at->toISOString(),
                ];
            });

        // 2. Get recent blood requests
        $requests = BloodRequest::with('organization')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($r) {
                $orgName = $r->organization ? $r->organization->name : 'Unknown Hospital';
                return [
                    'type' => 'Blood Request',
                    'title' => 'Blood Request: ' . $r->blood_group,
                    'subtitle' => $orgName . ' needs ' . $r->units_needed . ' units',
                    'time' => $r->created_at->diffForHumans(),
                    'timestamp' => $r->created_at->toISOString(),
                ];
            });

        // 3. Get recent delivery updates
        $deliveries = Delivery::with(['bloodRequest.organization', 'rider.user'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($del) {
                return [
                    'type' => 'Delivery',
                    'title' => 'Delivery Update: ' . $del->status,
                    'subtitle' => 'HT Code: ' . $del->tracking_code,
                    'time' => $del->updated_at->diffForHumans(),
                    'timestamp' => $del->updated_at->toISOString(),
                ];
            });

        // Combine and sort by timestamp
        $feed = $donations->concat($requests)->concat($deliveries)
            ->sortByDesc('timestamp')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $feed
        ]);
    }
}
