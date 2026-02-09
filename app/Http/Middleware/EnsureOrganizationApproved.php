<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If not authenticated, let the auth middleware handle it (or fail here)
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check if user is linked to an organization
        // For new auth pattern (User + Organization model), checks relationship
        if (in_array($user->role, ['facilities', 'blood_banks'])) {
            // Load organization if not loaded
            if (!$user->relationLoaded('linkedOrganization')) {
                $user->load('linkedOrganization');
            }

            $org = $user->linkedOrganization;

            if ($org && !$org->is_approved) {
                return response()->json([
                    'message' => 'Your organization account is pending approval by the regulatory body.',
                    'error' => 'Account Not Approved'
                ], 403);
            }
        }

        // Also check legacy Organization model auth if applicable (though AuthController favors User)
        // If the authenticated model IS an Organization instance
        if ($user instanceof \App\Models\Organization) {
            if (!$user->is_approved) {
                return response()->json([
                    'message' => 'Your organization account is pending approval by the regulatory body.',
                    'error' => 'Account Not Approved'
                ], 403);
            }
        }

        return $next($request);
    }
}
