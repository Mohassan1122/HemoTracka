<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * List all active subscription plans.
     */
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Get the current user's active subscription.
     */
    public function current(): JsonResponse
    {
        $user = Auth::user();
        $subscription = $user->subscriptions()
            ->with('plan')
            ->where('status', 'Active')
            ->where('ends_at', '>', now())
            ->first();

        return response()->json([
            'success' => true,
            'data' => $subscription
        ]);
    }

    /**
     * Subscribe the user to a plan.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::find($request->plan_id);

        // Cancel existing active subscriptions
        $user->subscriptions()
            ->where('status', 'Active')
            ->update(['status' => 'Cancelled']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration_days),
            'status' => 'Active',
            'auto_renew' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscribed successfully to ' . $plan->name,
            'data' => $subscription
        ], 201);
    }
}
