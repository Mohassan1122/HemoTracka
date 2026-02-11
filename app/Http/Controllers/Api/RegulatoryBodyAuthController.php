<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegulatoryBody;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RegulatoryBodyAuthController extends Controller
{
    /**
     * Register a new regulatory body (PAGE 1 - Registration).
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'institution_name' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'unique:regulatory_bodies'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'level' => ['required', 'in:federal,state'],
            'state_id' => ['required_if:level,state', 'nullable', 'exists:states,id'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'address' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->level === 'federal') {
                if (RegulatoryBody::where('level', 'federal')->exists()) {
                    $validator->errors()->add('level', 'A Federal Regulatory Body already exists. Only one is allowed.');
                }
            } elseif ($request->level === 'state' && $request->state_id) {
                if (RegulatoryBody::where('level', 'state')->where('state_id', $request->state_id)->exists()) {
                    $validator->errors()->add('state_id', 'A Regulatory Body for this state already exists.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Create user account
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->institution_name,
                'last_name' => '', // Regulatory bodies don't have a last name
                'role' => 'regulatory_body',
                'phone' => $request->phone_number ?? '', // Required by users table
            ]);

            // Create regulatory body record
            $regulatoryBody = RegulatoryBody::create([
                'user_id' => $user->id,
                'institution_name' => $request->institution_name,
                'license_number' => $request->license_number,
                'email' => $request->email,
                'level' => $request->level,
                'state_id' => $request->state_id,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'notification_preferences' => $this->getDefaultNotificationPreferences(),
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Regulatory body registered successfully.',
                'user' => $user,
                'regulatory_body' => $regulatoryBody,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Login for regulatory body (PAGE 1 - Login).
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials.'], 401);
        }

        // Check if user is a regulatory body
        if ($user->role !== 'regulatory_body') {
            return response()->json(['error' => 'Invalid user role.'], 403);
        }

        // Get regulatory body record
        $regulatoryBody = RegulatoryBody::where('user_id', $user->id)->first();

        if (!$regulatoryBody) {
            return response()->json(['error' => 'Regulatory body profile not found.'], 404);
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'regulatory_body' => $regulatoryBody->load(['state', 'socialConnections']),
            'token' => $token,
        ], 200);
    }

    /**
     * Logout for regulatory body.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.'], 200);
    }

    /**
     * Get default notification preferences.
     */
    private function getDefaultNotificationPreferences(): array
    {
        return [
            'blood_stock_alerts' => true,
            'fraud_detection' => true,
            'emergency_requests' => true,
            'event_participation' => true,
            'donor_retention' => true,
            'donor_feedback' => true,
            'donation_drive' => true,
            'emergency_notification' => true,
            'new_donor_registrations' => true,
        ];
    }
}
