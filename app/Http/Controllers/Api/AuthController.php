<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\Registered;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Build a structured user profile response.
     */
    private function buildUserProfile(User $user): array
    {
        $profileData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'profile_picture' => $user->profile_picture,
            'profile_picture_url' => $user->profile_picture_url,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'address' => $user->address,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'email_verified_at' => $user->email_verified_at,
        ];

        // Add organization data if user has one
        if ($user->organization_id) {
            $user->load('organization');
            $org = $user->organization;
            if ($org) {
                $profileData['organization'] = [
                    'id' => $org->id,
                    'name' => $org->name,
                    'type' => $org->type,
                    'email' => $org->contact_email,
                    'phone' => $org->phone,
                    'address' => $org->address,
                    'license_number' => $org->license_number,
                    'status' => $org->status,
                    'logo_url' => $org->logo_url,
                    'cover_photo_url' => $org->cover_photo_url,
                ];
            }
        }

        // Add donor data if user is a donor
        if ($user->role === 'donor') {
            $user->load('donor');
            $donor = $user->donor;
            if ($donor) {
                $profileData['donor'] = [
                    'id' => $donor->id,
                    'blood_group' => $donor->blood_group,
                    'genotype' => $donor->genotype,
                    'height' => $donor->height,
                    'address' => $donor->address,
                    'total_donations' => $donor->total_units_donated,
                    'next_eligible_date' => $donor->next_eligible_date,
                    'is_active' => $donor->is_active,
                ];
            }
        }

        // Add rider data if user is a rider
        if ($user->role === 'rider') {
            $user->load('rider');
            $rider = $user->rider;
            if ($rider) {
                $profileData['rider'] = [
                    'id' => $rider->id,
                    'license_number' => $rider->license_number,
                    'vehicle_type' => $rider->vehicle_type,
                    'is_available' => $rider->is_available,
                ];
            }
        }

        // Add organization data if user is facilities or blood_banks (new auth pattern)
        if (in_array($user->role, ['facilities', 'blood_banks'])) {
            $user->load('linkedOrganization');
            $org = $user->linkedOrganization;
            if ($org) {
                $profileData['organization'] = [
                    'id' => $org->id,
                    'name' => $org->name,
                    'type' => $org->type,
                    'role' => $org->role,
                    'email' => $org->email,
                    'phone' => $org->phone,
                    'address' => $org->address,
                    'license_number' => $org->license_number,
                    'status' => $org->status,
                    'logo_url' => $org->logo_url,
                    'cover_photo_url' => $org->cover_photo_url,
                    'latitude' => $org->latitude,
                    'longitude' => $org->longitude,
                    'operating_hours' => $org->operating_hours,
                    'services' => $org->services,
                    'description' => $org->description,
                ];
            }
        }

        return $profileData;
    }

    /**
     * Build a structured organization profile response.
     */
    private function buildOrganizationProfile(\App\Models\Organization $org): array
    {
        return [
            'id' => $org->id,
            'name' => $org->name,
            'type' => $org->type,
            'role' => $org->role ?? ($org->type === 'Hospital' ? 'facilities' : 'blood_banks'),
            'email' => $org->contact_email,
            'phone' => $org->phone,
            'address' => $org->address,
            'license_number' => $org->license_number,
            'status' => $org->status,
            'logo_url' => $org->logo_url,
            'cover_photo_url' => $org->cover_photo_url,
            'description' => $org->description,
            'services' => $org->services,
            'operating_hours' => $org->operating_hours,
            'facebook_link' => $org->facebook_link,
            'twitter_link' => $org->twitter_link,
            'instagram_link' => $org->instagram_link,
            'linkedin_link' => $org->linkedin_link,
            'latitude' => $org->latitude,
            'longitude' => $org->longitude,
        ];
    }

    /**
     * Register a new user or organization.
     */
    public function register(Request $request): JsonResponse
    {
        $role = $request->input('role', 'donor');

        if (in_array($role, ['facilities', 'blood_banks'])) {
            return $this->registerOrganization($request);
        }

        return $this->registerUser($request);
    }

    private function registerUser(Request $request): JsonResponse
    {
        $role = $request->input('role', 'donor');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'role' => ['nullable', 'in:admin,donor,rider'],
            'profile_picture' => ['nullable', 'image', 'max:2048'], // Optional profile picture
            // Donor-specific fields
            'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'genotype' => ['nullable', 'string', 'max:10'],
            'height' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // Handle profile picture upload if provided
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('profile_pictures', $filename, 'public');
            $validated['profile_picture'] = $path;
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'role' => $validated['role'] ?? 'donor',
            'profile_picture' => $validated['profile_picture'] ?? null,
        ]);

        // If the user is registering as a donor, also create a donor record
        if ($role === 'donor') {
            $donorData = [
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'blood_group' => $validated['blood_group'] ?? null,
                'genotype' => $validated['genotype'] ?? null,
                'height' => $validated['height'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'Eligible', // Default status
            ];

            \App\Models\Donor::create($donorData);
        }

        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user->load(['donor', 'organization']), // Load the donor relationship
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    private function registerOrganization(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:100', 'unique:organizations'],
            'address' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'], // Now unique in users table
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'type' => ['required', 'in:Hospital,Blood Bank'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        // Determine role based on type
        $role = $validated['type'] === 'Hospital' ? 'facilities' : 'blood_banks';

        // Create User record first (for auth)
        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['name'],
            'last_name' => '',
            'role' => $role,
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        // Create Organization linked to User
        $organization = \App\Models\Organization::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'license_number' => $validated['license_number'],
            'address' => $validated['address'],
            'email' => $validated['email'],
            'contact_email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']), // Keep for backward compatibility
            'type' => $validated['type'],
            'role' => $role,
            'status' => 'Active',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        // Create token from User (new auth pattern)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Organization registration successful.',
            'user' => $user->load('linkedOrganization'),
            'organization' => $organization,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user/organization and create token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 1. Try finding User
        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            // User exists, check password
            if (Hash::check($validated['password'], $user->password)) {
                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'message' => 'Login successful',
                    'user' => $this->buildUserProfile($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]);
            } else {
                // Password is incorrect
                return response()->json([
                    'message' => 'Incorrect password',
                ], 401);
            }
        }

        // 2. Try finding Organization
        $org = \App\Models\Organization::where('email', $validated['email'])->first();
        if ($org) {
            // Organization exists, check password
            if (Hash::check($validated['password'], $org->password)) {
                $token = $org->createToken('auth_token')->plainTextToken;
                $orgProfile = $this->buildOrganizationProfile($org);
                return response()->json([
                    'message' => 'Login successful',
                    'user' => $orgProfile,
                    'role' => $orgProfile['role'],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]);
            } else {
                // Password is incorrect
                return response()->json([
                    'message' => 'Incorrect password',
                ], 401);
            }
        }

        // Neither user nor organization exists with this email
        return response()->json([
            'message' => 'Account does not exist',
        ], 401);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $authenticatedModel = $request->user();

        if ($authenticatedModel instanceof \App\Models\Organization) {
            $orgProfile = $this->buildOrganizationProfile($authenticatedModel);
            return response()->json([
                'user' => $orgProfile,
                'role' => $orgProfile['role'],
            ]);
        }

        $userProfile = $this->buildUserProfile($authenticatedModel);
        return response()->json([
            'user' => $userProfile,
            'role' => $userProfile['role'],
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $authenticatedModel = $request->user();

        // Check if authenticated model is Organization
        if ($authenticatedModel instanceof \App\Models\Organization) {
            return response()->json([
                'message' => 'Organization profile updates should use the organization endpoints',
            ], 400);
        }

        $user = $authenticatedModel;

        // Prepare validation rules
        $rules = [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'address' => ['sometimes', 'string', 'nullable'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90', 'nullable'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180', 'nullable'],
        ];

        // Add profile picture validation if it's present in the request
        if ($request->hasFile('profile_picture')) {
            $rules['profile_picture'] = ['sometimes', 'image', 'max:2048'];
        }

        // Add donor-specific fields validation if user is a donor
        if ($user->role === 'donor' && $user->donor) {
            $rules = array_merge($rules, [
                'blood_group' => ['sometimes', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
                'genotype' => ['sometimes', 'string', 'max:10'],
                'height' => ['sometimes', 'string', 'max:10'],
                'notes' => ['sometimes', 'string'],
            ]);
        }

        $validated = $request->validate($rules);

        // Handle profile picture upload if provided
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('profile_pictures', $filename, 'public');
            $validated['profile_picture'] = $path;
        }

        // Update user table (including address, latitude, longitude)
        $user->update(array_filter($validated, function ($key) {
            return in_array($key, [
                'first_name',
                'last_name',
                'phone',
                'date_of_birth',
                'gender',
                'profile_picture',
                'address',
                'latitude',
                'longitude'
            ]);
        }, ARRAY_FILTER_USE_KEY));

        // If user is a donor, also update the donor table
        if ($user->role === 'donor' && $user->donor) {
            $donorUpdates = array_filter($validated, function ($key) {
                return in_array($key, [
                    'first_name',
                    'last_name',
                    'blood_group',
                    'genotype',
                    'height',
                    'date_of_birth',
                    'address',
                    'phone',
                    'notes'
                ]);
            }, ARRAY_FILTER_USE_KEY);

            if (!empty($donorUpdates)) {
                $user->donor->update($donorUpdates);
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $this->buildUserProfile($user->fresh()),
        ]);
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Generate a token
        $token = \Illuminate\Support\Str::random(64);

        // Store token in password_reset_tokens table
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // In production, send email with token
        Mail::to($user->email)->send(new PasswordResetMail($user, $token));

        return response()->json([
            'message' => 'Password reset link sent to your email',
        ]);
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Verify token
        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$record || !Hash::check($validated['token'], $record->token)) {
            return response()->json([
                'message' => 'Invalid or expired reset token',
            ], 422);
        }

        // Check if token is expired (1 hour)
        if (now()->diffInMinutes($record->created_at) > 60) {
            return response()->json([
                'message' => 'Reset token has expired',
            ], 422);
        }

        // Update password
        $user = User::where('email', $validated['email'])->first();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Delete the token
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }

    /**
     * Verify email address.
     */
    public function verifyEmail(Request $request, $id, $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Invalid verification link',
            ], 422);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }

    /**
     * Resend email verification.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent',
        ]);
    }
}
