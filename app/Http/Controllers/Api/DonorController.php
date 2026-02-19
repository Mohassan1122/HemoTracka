<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonorController extends Controller
{
    /**
     * Display a listing of donors.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Donor::with(['organization', 'user']);

        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $donors = $query->paginate($request->get('per_page', 15));

        return response()->json($donors);
    }

    /**
     * Store a newly created donor.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id', 'unique:donors'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'genotype' => ['nullable', 'string', 'max:10'],
            'date_of_birth' => ['required', 'date'],
            'address' => ['nullable', 'string'],
            'phone' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:Eligible,Permanently Deferral,Temporary Deferral'],
        ]);

        $donor = Donor::create($validated);

        return response()->json([
            'message' => 'Donor created successfully',
            'donor' => $donor->load('organization'),
        ], 201);
    }

    /**
     * Display the specified donor.
     */
    public function show(Donor $donor): JsonResponse
    {
        return response()->json([
            'donor' => $donor->load(['organization', 'user', 'donations']),
        ]);
    }

    /**
     * Update the specified donor.
     */
    public function update(Request $request, Donor $donor): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'blood_group' => ['sometimes', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'genotype' => ['nullable', 'string', 'max:10'],
            'date_of_birth' => ['sometimes', 'date'],
            'address' => ['nullable', 'string'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:Eligible,Permanently Deferral,Temporary Deferral'],
        ]);

        $donor->update($validated);

        return response()->json([
            'message' => 'Donor updated successfully',
            'donor' => $donor->fresh(),
        ]);
    }

    /**
     * Remove the specified donor.
     */
    public function destroy(Donor $donor): JsonResponse
    {
        $donor->delete();

        return response()->json([
            'message' => 'Donor deleted successfully',
        ]);
    }

    /**
     * Get donor's donation history.
     */
    public function donations(Donor $donor): JsonResponse
    {
        return response()->json([
            'donations' => $donor->donations()->with('organization')->latest('donation_date')->get(),
        ]);
    }

    /**
     * Check donor eligibility to donate.
     */
    public function eligibility(Donor $donor): JsonResponse
    {
        $isEligible = $donor->isEligibleToDonate();
        $nextEligibleDate = $donor->next_eligible_date;
        $daysUntilEligible = $nextEligibleDate ? max(0, now()->diffInDays($nextEligibleDate, false)) : 0;

        return response()->json([
            'donor_id' => $donor->id,
            'is_eligible' => $isEligible,
            'status' => $donor->status,
            'last_donation_date' => $donor->last_donation_date?->toDateString(),
            'next_eligible_date' => $nextEligibleDate?->toDateString(),
            'days_until_eligible' => $isEligible ? 0 : (int) $daysUntilEligible,
            'message' => $this->getEligibilityMessage($donor, $isEligible, $daysUntilEligible),
        ]);
    }

    /**
     * Get donor dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $donor = $user->donor;

        if (!$donor) {
            return response()->json(['message' => 'Donor profile not found for this user.'], 404);
        }

        $donor->load(['donations', 'badges', 'appointments']);

        $recentDonations = $donor->donations()
            ->with('organization')
            ->latest('donation_date')
            ->limit(5)
            ->get();

        $upcomingAppointments = $donor->appointments()
            ->with('organization')
            ->upcoming()
            ->limit(3)
            ->get();

        $badges = $donor->badges()
            ->orderByPivot('earned_at', 'desc')
            ->get();

        return response()->json([
            'donor' => [
                'id' => $donor->id,
                'name' => $user->full_name,
                'blood_group' => $donor->blood_group,
                'status' => $donor->status,
            ],
            'stats' => [
                'total_donations' => $donor->donations()->count(),
                'total_units_donated' => $donor->total_units_donated,
                'total_badges' => $badges->count(),
                'total_points' => $donor->total_points,
                'global_donations_count' => \App\Models\Donation::count(),
            ],
            'eligibility' => [
                'is_eligible' => $donor->isEligibleToDonate(),
                'next_eligible_date' => $donor->next_eligible_date?->toDateString(),
            ],
            'recent_donations' => $recentDonations,
            'upcoming_appointments' => $upcomingAppointments,
            'badges' => $badges,
            'quick_actions' => [
                ['title' => 'Find Blood Banks', 'icon' => 'search', 'route' => 'donor.blood-banks'],
                ['title' => 'Schedule', 'icon' => 'calendar', 'route' => 'donor.appointments.create'],
                ['title' => 'My Badges', 'icon' => 'award', 'route' => 'donor.badges'],
            ]
        ]);
    }

    /**
     * Get eligibility message.
     */
    private function getEligibilityMessage(Donor $donor, bool $isEligible, int $daysUntilEligible): string
    {
        if ($donor->status === 'Permanently Deferral') {
            return 'You are permanently deferred from donating blood.';
        }

        if ($donor->status === 'Temporary Deferral') {
            return 'You are temporarily deferred from donating blood. Please consult with a healthcare provider.';
        }

        if ($isEligible) {
            return 'You are eligible to donate blood!';
        }

        return "You can donate again in {$daysUntilEligible} days.";
    }

    /**
     * Upload profile picture for the authenticated donor user.
     */
    public function uploadProfilePicture(Request $request): JsonResponse
    {
        $request->validate([
            'profile_picture' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        $user = $request->user();

        if ($user->role !== 'donor') {
            return response()->json([
                'message' => 'Only donors can use this endpoint',
            ], 403);
        }

        // Delete old profile picture if exists
        if ($user->profile_picture && \Storage::disk('public')->exists($user->profile_picture)) {
            \Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new profile picture
        $image = $request->file('profile_picture');
        $filename = time() . '_' . $user->id . '_' . $image->getClientOriginalName();
        $path = $image->storeAs('profile_pictures', $filename, 'public');

        $user->update(['profile_picture' => $path]);

        return response()->json([
            'message' => 'Profile picture uploaded successfully',
            'profile_picture_url' => $user->profile_picture_url,
        ]);
    }
    /**
     * Get donor's medical record and stats.
     */
    public function medicalRecord(Request $request): JsonResponse
    {
        $user = $request->user();
        $donor = $user->donor;

        if (!$donor) {
            return response()->json(['message' => 'Donor profile not found.'], 404);
        }

        // Calculate stats
        $totalBloodUnits = $donor->donations()->sum('units');

        // Mock data for unsupported types (future proofing)
        $totalBoneMarrow = 0;
        $totalPlatelets = 0;

        return response()->json([
            'personal_info' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'other_names' => $donor->other_names,
                'gender' => $user->gender,
                'date_of_birth' => $user->date_of_birth ? $user->date_of_birth->format('F j, Y') : null,
            ],
            'health_info' => [
                'blood_group' => $donor->blood_group,
                'genotype' => $donor->genotype,
            ],
            'donation_stats' => [
                'blood_units' => $totalBloodUnits,
                'bone_marrow_units' => $totalBoneMarrow,
                'platelets_units' => $totalPlatelets,
                'last_donation_date' => $donor->last_donation_date ? $donor->last_donation_date->format('F j, Y') : 'Never',
            ]
        ]);
    }
}
