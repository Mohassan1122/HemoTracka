<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessageSent;
use App\Events\OrganizationStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BloodRequest;
use App\Models\Delivery;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Feedback;
use App\Models\InventoryItem;
use App\Models\Message;
use App\Models\Organization;
use App\Models\RegulatoryBody;
use App\Models\Rider;
use App\Models\User;
use App\Notifications\OrganizationStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AdminController extends Controller
{
    /**
     * Platform Overview Dashboard.
     */
    public function dashboard(): JsonResponse
    {
        // 1. KPI Cards
        $totalSubscriptions = DB::table('payments')->where('status', 'success')->sum('amount'); // Assuming payments table exists and has amount
        if (!$totalSubscriptions)
            $totalSubscriptions = 0;

        $stats = [
            'active_donors_count' => Donor::where('status', 'Eligible')->count(),
            'total_subscriptions_amount' => $totalSubscriptions,
            'facilities_count' => Organization::whereIn('type', ['Hospital', 'Clinic'])->count(),
            'total_users_count' => User::count(),
        ];

        // 2. Lists (Latest 5)
        $lists = [
            'blood_donors' => Donor::with('user')->latest()->take(5)->get()->map(function ($donor) {
                return [
                    'id' => $donor->id,
                    'name' => $donor->user ? $donor->user->full_name : $donor->first_name . ' ' . $donor->last_name,
                    'blood_group' => $donor->blood_group,
                    'last_donation' => $donor->last_donation_date,
                    'status' => $donor->status,
                ];
            }),
            'admins' => User::where('role', 'admin')->latest()->take(5)->get()->map(function ($admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->full_name,
                    'email' => $admin->email,
                    'status' => 'Active', // Admins are active if they can login
                ];
            }),
            'blood_banks' => Organization::where('type', 'Blood Bank')->latest()->take(5)->get()->map(function ($bank) {
                return [
                    'id' => $bank->id,
                    'name' => $bank->name,
                    'state' => $bank->state ? $bank->state->name : 'N/A',
                    'contact' => $bank->phone,
                ];
            }),
            'most_active_banks' => Organization::where('type', 'Blood Bank')
                ->withCount('donations')
                ->orderByDesc('donations_count')
                ->take(5)
                ->get()
                ->map(function ($bank) {
                    return [
                        'id' => $bank->id,
                        'name' => $bank->name,
                        'donations_count' => $bank->donations_count,
                        'is_verified' => $bank->is_approved,
                    ];
                }),
        ];

        // 3. Charts

        // A. Regional Distribution (Donors & Banks)
        // Trying to use State model if available, otherwise grouping by address/state string if simple
        // Assuming Organization has state_id
        $highDonorRegions = Organization::select('state_id', DB::raw('count(*) as total'))
            ->where('type', 'Blood Bank') // Approximation: Donors registered via Banks in a state
            ->with('state')
            ->groupBy('state_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->state ? $item->state->name : 'Unknown',
                    'value' => $item->total
                ];
            });

        $banksInRange = Organization::select('state_id', DB::raw('count(*) as total'))
            ->where('type', 'Blood Bank')
            ->with('state')
            ->groupBy('state_id')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->state ? $item->state->name : 'Unknown',
                    'value' => $item->total
                ];
            });


        // B. Blood Supply (Inventory by Group)
        $bloodSupply = InventoryItem::select('blood_group', DB::raw('SUM(units_in_stock) as total_units'))
            ->groupBy('blood_group')
            ->get();

        // C. Yearly History (Donations per month)
        $yearlyHistory = Donation::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill missing months
        $yearlyHistoryData = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($months as $index => $month) {
            $found = $yearlyHistory->firstWhere('month', $index + 1);
            $yearlyHistoryData[] = $found ? $found->count : 0;
        }

        // D. Blood Demand (Requests per month - simplified for chart)
        $bloodDemand = BloodRequest::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $bloodDemandData = [];
        foreach ($months as $index => $month) {
            $found = $bloodDemand->firstWhere('month', $index + 1);
            $bloodDemandData[] = $found ? $found->count : 0;
        }


        // E. Demographics (Age & Gender)
        $genderStats = Donor::select('gender', DB::raw('count(*) as total')) // Assuming gender is on Donor or linked User
            // Check if gender is on Donor or User. User has gender.
            ->join('users', 'donors.user_id', '=', 'users.id')
            ->groupBy('gender')
            ->get();

        $ageStats = Donor::join('users', 'donors.user_id', '=', 'users.id')
            ->select(DB::raw('TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) as age'))
            ->get()
            ->groupBy(function ($item) {
                if ($item->age < 25)
                    return '18-24';
                if ($item->age < 35)
                    return '25-34';
                if ($item->age < 45)
                    return '35-44';
                return '45+';
            })
            ->map(fn($group) => $group->count());


        $charts = [
            'high_donor_regions' => $highDonorRegions,
            'blood_supply' => $bloodSupply, // {blood_group, total_units}
            'banks_in_range' => $banksInRange,
            'yearly_history' => [
                'categories' => $months,
                'data' => $yearlyHistoryData
            ],
            'blood_types_in_bank' => $bloodSupply, // Same source
            'age_demographics' => $ageStats,
            'gender_demographics' => $genderStats,
            'blood_demand' => [
                'categories' => $months,
                'data' => $bloodDemandData
            ],
            'global_blood_types' => Donor::select('blood_group', DB::raw('count(*) as total'))
                ->groupBy('blood_group')
                ->get()
        ];

        // F. Recent Activity used previously
        $recentActivity = $this->getRecentActivity();


        return response()->json([
            'stats' => $stats,
            'lists' => $lists,
            'charts' => $charts,
            'recent_activity' => $recentActivity
        ]);
    }

    /**
     * List all organizations with advanced filtering.
     */
    public function organizations(Request $request): JsonResponse
    {
        $query = Organization::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $organizations = $query->withCount(['donors', 'bloodRequests'])
            ->paginate($request->get('per_page', 15));

        return response()->json($organizations);
    }

    /**
     * Approve or update organization status.
     */
    public function updateOrganizationStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Pending,Active,Suspended'],
        ]);

        $org = Organization::findOrFail($id);
        $org->update(['status' => $validated['status']]);

        // Notify organization owner
        if ($org->user) {
            Notification::send($org->user, new OrganizationStatusNotification($org));
        }

        return response()->json([
            'message' => "Organization status updated to {$validated['status']}",
            'organization' => $org
        ]);
    }

    /**
     * Global User Management.
     */
    /**
     * Get Roles Overview & User List.
     */
    public function roles(Request $request): JsonResponse
    {
        // 1. Get Role Stats (Counts)
        $roleCounts = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        // Ensure all valid roles are present with 0 count if no users
        $validRoles = User::getValidRoles();
        $stats = [];
        foreach ($validRoles as $key => $label) {
            $stats[] = [
                'role' => $key,
                'label' => $label,
                'count' => $roleCounts[$key] ?? 0,
            ];
        }

        // 2. Get Users List (Filtered by Role if provided)
        $query = User::query();

        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        $users = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    /**
     * Get all users.
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Use linkedOrganization since organization_id was removed from users
        $users = $query->with('linkedOrganization')
            ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * List all donors across the platform.
     */
    public function donors(Request $request): JsonResponse
    {
        $query = Donor::query();

        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        $donors = $query->with(['user.state', 'organization'])
            ->withCount('donations')
            ->paginate($request->get('per_page', 15));

        return response()->json($donors);
    }

    /**
     * Rider and Logistics Oversight.
     */
    public function logistics(): JsonResponse
    {
        $stats = [
            'total_riders' => Rider::count(),
            'active_deliveries' => Delivery::whereIn('status', ['Order Taken', 'Preparing', 'Product Ready', 'En Route'])->count(),
            'completed_deliveries' => Delivery::where('status', 'Delivered')->count(),
            'rider_status_breakdown' => Rider::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Global Feedback Review.
     */
    public function feedback(): JsonResponse
    {
        $feedbacks = Feedback::with(['user', 'organization'])
            ->latest()
            ->paginate(15);

        return response()->json($feedbacks);
    }

    /**
     * Get aggregated statistics for organizations.
     */
    public function organizationStats(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $query = Organization::query();

        if ($type) {
            $query->where('type', $type);
        }

        $stats = [
            'total_facilities' => $query->count(),
            'total_donations' => $query->withCount('donations')->get()->sum('donations_count'),
            'total_requests' => $query->withCount('bloodRequests')->get()->sum('blood_requests_count'),
            'verified_count' => $query->where('is_approved', true)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get details for a specific organization.
     */
    public function organizationDetails($id): JsonResponse
    {
        $org = Organization::with(['state', 'donations', 'bloodRequests.user', 'inventoryItems'])
            ->withCount(['donations', 'bloodRequests', 'reviews'])
            ->findOrFail($id);

        // Calculate aggregate stats for this org
        $stats = [
            'total_donations' => $org->donations_count,
            'total_requests' => $org->blood_requests_count,
            'total_reviews' => $org->reviews_count,
        ];

        // Recent activity lists
        $recentDonors = $org->donations()
            ->with('donor.user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($donation) {
                return [
                    'name' => $donation->donor->user->full_name ?? 'Unknown',
                    'amount' => $donation->amount . ' Pint',
                    'date' => $donation->created_at->format('d - M - Y, H:i'),
                ];
            });

        $recentRequests = $org->bloodRequests()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($request) {
                return [
                    'name' => $request->patient_name ?? 'Unknown', // Using patient name or user name
                    'amount' => $request->units_needed . ' Units',
                    'date' => $request->created_at->format('d - M - Y, H:i'),
                ];
            });

        // Blood Inventory breakdown
        $bloodTypes = $org->inventoryItems()
            ->select('blood_group', DB::raw('sum(units_in_stock) as total'))
            ->groupBy('blood_group')
            ->get();

        $inventoryTotal = $org->inventoryItems()->sum('units_in_stock');

        return response()->json([
            'organization' => $org,
            'stats' => $stats,
            'recent_donors' => $recentDonors,
            'recent_requests' => $recentRequests,
            'blood_types' => $bloodTypes,
            'inventory_total' => $inventoryTotal,
            'pending_requests_count' => $org->bloodRequests()->where('status', 'Pending')->count(),
        ]);
    }

    // Donors list is already present in the file (public function donors), 
    // but the previous file view showed it. I will double check if I need to re-add it or just the others.
    // The previous view showed 'public function donors' at line 272.
    // So I only need to add organizationStats and organizationDetails.
    // However, I need to fix the 'users()' method in this controller which assumes a relationship that fails.

    // =========================================================================
    // MESSAGING & ALERTS
    // =========================================================================

    /**
     * Get conversations list for Admin
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');

            $query = Message::where(function ($q) use ($request) {
                $q->where('to_user_id', $request->user()->id)
                    ->orWhere('from_user_id', $request->user()->id);
            })
                ->select(
                    DB::raw('CASE WHEN from_user_id = ' . $request->user()->id . ' THEN to_user_id ELSE from_user_id END as other_user_id'),
                    DB::raw('MAX(created_at) as last_message_at'),
                    DB::raw('SUM(CASE WHEN to_user_id = ' . $request->user()->id . ' AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count')
                )
                ->groupBy('other_user_id')
                ->orderByDesc('last_message_at');

            $conversations = $query->paginate($perPage, ['*'], 'page', $page);

            $conversations->getCollection()->transform(function ($conv) {
                $otherUser = \App\Models\User::find($conv->other_user_id);
                $organization = Organization::where('user_id', $conv->other_user_id)->first();
                $regBody = RegulatoryBody::where('user_id', $conv->other_user_id)->first();

                $name = 'Unknown';
                if ($organization) {
                    $name = $organization->name . ' (' . ucfirst($organization->type) . ')';
                } elseif ($regBody) {
                    $name = $regBody->institution_name . ' (Regulatory)';
                } elseif ($otherUser) {
                    $name = $otherUser->first_name . ' ' . $otherUser->last_name . ' (' . ucfirst($otherUser->role) . ')';
                }

                return [
                    'id' => $conv->other_user_id,
                    'organization_name' => $name,
                    'last_message_at' => $conv->last_message_at,
                    'unread_count' => $conv->unread_count,
                    'user' => $otherUser
                ];
            });

            if ($search) {
                $filtered = $conversations->getCollection()->filter(function ($item) use ($search) {
                    return stripos($item['organization_name'], $search) !== false;
                });
                $conversations->setCollection($filtered);
            }

            return response()->json([
                'conversations' => $conversations->items(),
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                    'last_page' => $conversations->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get conversation thread for Admin
     */
    public function getConversation(Request $request, $conversationId): JsonResponse
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);

            $messages = Message::where(function ($q) use ($request, $conversationId) {
                $q->where(function ($q2) use ($request, $conversationId) {
                    $q2->where('from_user_id', $request->user()->id)
                        ->where('to_user_id', $conversationId);
                })->orWhere(function ($q2) use ($request, $conversationId) {
                    $q2->where('from_user_id', $conversationId)
                        ->where('to_user_id', $request->user()->id);
                });
            })
                ->orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            Message::where('to_user_id', $request->user()->id)
                ->where('from_user_id', $conversationId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $otherUser = \App\Models\User::find($conversationId);
            $organization = Organization::where('user_id', $conversationId)->first();
            $regBody = RegulatoryBody::where('user_id', $conversationId)->first();

            $name = 'Unknown';
            if ($organization) {
                $name = $organization->name;
            } elseif ($regBody) {
                $name = $regBody->institution_name;
            } elseif ($otherUser) {
                $name = $otherUser->first_name . ' ' . $otherUser->last_name;
            }

            return response()->json([
                'conversation' => [
                    'user_id' => $conversationId,
                    'name' => $name,
                    'organization_id' => $organization ? $organization->id : null,
                ],
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'last_page' => $messages->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send message for Admin
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => ['required', 'exists:users,id'],
            'content' => ['required', 'string'],
        ]);

        try {
            $message = Message::create([
                'from_user_id' => $request->user()->id,
                'to_user_id' => $request->input('receiver_id'),
                'body' => $request->input('content'),
            ]);

            event(new NewMessageSent($message));

            return response()->json([
                'message' => 'Message sent successfully.',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create alert message (Broadcast) for Admin
     */
    public function createAlert(Request $request): JsonResponse
    {
        $request->validate([
            'organization_ids' => ['required', 'array'],
            'organization_ids.*' => ['exists:organizations,id'],
            'title' => ['required', 'string'],
            'content' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
        ]);

        try {
            $messages = [];
            foreach ($request->organization_ids as $orgId) {
                $organization = Organization::find($orgId);

                if ($organization && $organization->user_id) {
                    $priority = $request->input('priority', 'high');
                    $bodyContent = "[ADMIN ALERT - " . strtoupper($priority) . "]\n\n" . $request->input('content');

                    $message = Message::create([
                        'from_user_id' => $request->user()->id,
                        'to_user_id' => $organization->user_id,
                        'body' => $bodyContent,
                        'subject' => $request->input('title'),
                    ]);

                    event(new NewMessageSent($message));

                    $messages[] = $message;
                }
            }

            return response()->json([
                'message' => 'Alert sent to ' . count($messages) . ' organization(s).',
                'alerts' => $messages,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to get recent system activity.
     */
    private function getRecentActivity()
    {
        // Mocking an activity feed since we don't have a dedicated Activity model yet
        // In a real app, this would query an audit_logs table
        return [
            ['type' => 'registration', 'message' => 'New Blood Bank registered: PH Central Bank', 'time' => now()->subMinutes(15)],
            ['type' => 'appointment', 'message' => 'Donor Caleb scheduled an appointment', 'time' => now()->subHours(2)],
            ['type' => 'delivery', 'message' => 'Fast Rider delivered 10 units to BMH', 'time' => now()->subHours(5)],
        ];
    }
}
