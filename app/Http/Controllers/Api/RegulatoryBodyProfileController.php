<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegulatoryBody;
use App\Models\RegulatoryBodySocialConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RegulatoryBodyProfileController extends Controller
{
    /**
     * Get the authenticated regulatory body's profile (PAGE 2 - View Profile).
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)
                ->with(['state', 'socialConnections'])
                ->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            return response()->json([
                'regulatory_body' => $regulatoryBody,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the regulatory body's profile (PAGE 2 - Edit Profile).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'institution_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'work_days' => ['nullable', 'string'], // e.g., "Mon-Sat"
            'work_hours' => ['nullable', 'string'], // e.g., "8am-6pm"
            'company_website' => ['nullable', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $regulatoryBody->update($validator->validated());

            return response()->json([
                'message' => 'Profile updated successfully.',
                'regulatory_body' => $regulatoryBody->load(['state', 'socialConnections']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload profile picture (PAGE 2 - Upload Profile Picture).
     */
    public function uploadProfilePicture(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            // Delete old picture if exists
            if ($regulatoryBody->profile_picture_url && Storage::disk('public')->exists($regulatoryBody->profile_picture_url)) {
                Storage::disk('public')->delete($regulatoryBody->profile_picture_url);
            }

            // Store new picture
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('regulatory_bodies/profile_pictures', $filename, 'public');

            // Update regulatory body
            $regulatoryBody->update(['profile_picture_url' => $path]);

            return response()->json([
                'message' => 'Profile picture uploaded successfully.',
                'url' => Storage::url($path),
                'regulatory_body' => $regulatoryBody,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload cover picture (PAGE 2 - Upload Cover Picture).
     */
    public function uploadCoverPicture(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            // Delete old picture if exists
            if ($regulatoryBody->cover_picture_url && Storage::disk('public')->exists($regulatoryBody->cover_picture_url)) {
                Storage::disk('public')->delete($regulatoryBody->cover_picture_url);
            }

            // Store new picture
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('regulatory_bodies/cover_pictures', $filename, 'public');

            // Update regulatory body
            $regulatoryBody->update(['cover_picture_url' => $path]);

            return response()->json([
                'message' => 'Cover picture uploaded successfully.',
                'url' => Storage::url($path),
                'regulatory_body' => $regulatoryBody,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add social connection (PAGE 2 - Add Social Connection).
     */
    public function addSocialConnection(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => ['required', 'in:instagram,twitter,facebook,linkedin,youtube,tiktok'],
            'handle' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            // Check if platform already exists
            $existing = RegulatoryBodySocialConnection::where('regulatory_body_id', $regulatoryBody->id)
                ->where('platform', $request->platform)
                ->first();

            if ($existing) {
                return response()->json(['error' => 'Social connection for this platform already exists.'], 409);
            }

            $socialConnection = RegulatoryBodySocialConnection::create([
                'regulatory_body_id' => $regulatoryBody->id,
                'platform' => $request->platform,
                'handle' => $request->handle,
            ]);

            return response()->json([
                'message' => 'Social connection added successfully.',
                'social_connection' => $socialConnection,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update social connection (PAGE 2 - Edit Social Connection).
     */
    public function updateSocialConnection(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'handle' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $socialConnection = RegulatoryBodySocialConnection::where('id', $id)
                ->where('regulatory_body_id', $regulatoryBody->id)
                ->first();

            if (!$socialConnection) {
                return response()->json(['error' => 'Social connection not found.'], 404);
            }

            $socialConnection->update(['handle' => $request->handle]);

            return response()->json([
                'message' => 'Social connection updated successfully.',
                'social_connection' => $socialConnection,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete social connection (PAGE 2 - Remove Social Connection).
     */
    public function deleteSocialConnection(Request $request, $id): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $socialConnection = RegulatoryBodySocialConnection::where('id', $id)
                ->where('regulatory_body_id', $regulatoryBody->id)
                ->first();

            if (!$socialConnection) {
                return response()->json(['error' => 'Social connection not found.'], 404);
            }

            $socialConnection->delete();

            return response()->json(['message' => 'Social connection deleted successfully.'], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
