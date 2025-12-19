<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donor;
use App\Models\DonorBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonorBadgeController extends Controller
{
    /**
     * Display a listing of all available badges.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DonorBadge::query();

        if (!$request->has('include_inactive')) {
            $query->active();
        }

        $badges = $query->orderBy('points', 'desc')->get();

        return response()->json([
            'badges' => $badges,
        ]);
    }

    /**
     * Display the specified badge.
     */
    public function show(DonorBadge $badge): JsonResponse
    {
        return response()->json([
            'badge' => $badge,
        ]);
    }

    /**
     * Store a newly created badge (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:donor_badges'],
            'description' => ['required', 'string'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:7'],
            'criteria_type' => ['required', 'in:donation_count,units_donated,consecutive_donations,first_donation,referral_count,blood_type_rare'],
            'criteria_value' => ['required', 'integer', 'min:1'],
            'points' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $badge = DonorBadge::create($validated);

        return response()->json([
            'message' => 'Badge created successfully',
            'badge' => $badge,
        ], 201);
    }

    /**
     * Update the specified badge.
     */
    public function update(Request $request, DonorBadge $badge): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:7'],
            'criteria_type' => ['sometimes', 'in:donation_count,units_donated,consecutive_donations,first_donation,referral_count,blood_type_rare'],
            'criteria_value' => ['sometimes', 'integer', 'min:1'],
            'points' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $badge->update($validated);

        return response()->json([
            'message' => 'Badge updated successfully',
            'badge' => $badge->fresh(),
        ]);
    }

    /**
     * Get badges earned by a specific donor.
     */
    public function donorBadges(Donor $donor): JsonResponse
    {
        $badges = $donor->badges()
            ->orderByPivot('earned_at', 'desc')
            ->get();

        $totalPoints = $badges->sum('points');

        return response()->json([
            'donor_id' => $donor->id,
            'badges' => $badges,
            'total_badges' => $badges->count(),
            'total_points' => $totalPoints,
        ]);
    }

    /**
     * Check and award badges to a donor.
     */
    public function checkAndAward(Request $request, Donor $donor): JsonResponse
    {
        $availableBadges = DonorBadge::active()->get();
        $earnedBadgeIds = $donor->badges()->pluck('donor_badges.id')->toArray();
        $newlyAwarded = [];

        foreach ($availableBadges as $badge) {
            // Skip if already earned
            if (in_array($badge->id, $earnedBadgeIds)) {
                continue;
            }

            // Check eligibility
            if ($badge->checkEligibility($donor)) {
                $donor->badges()->attach($badge->id, [
                    'earned_at' => now(),
                ]);
                $newlyAwarded[] = $badge;
            }
        }

        return response()->json([
            'message' => count($newlyAwarded) > 0
                ? 'New badges awarded!'
                : 'No new badges earned',
            'newly_awarded' => $newlyAwarded,
            'total_badges' => $donor->badges()->count(),
            'total_points' => $donor->badges()->sum('points'),
        ]);
    }

    /**
     * Get leaderboard of donors by points.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $leaderboard = Donor::withSum('badges as total_points', 'points')
            ->withCount('badges')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get()
            ->map(fn($donor) => [
                'id' => $donor->id,
                'name' => $donor->full_name,
                'blood_group' => $donor->blood_group,
                'total_badges' => $donor->badges_count,
                'total_points' => $donor->total_points ?? 0,
            ]);

        return response()->json([
            'leaderboard' => $leaderboard,
        ]);
    }
}
