<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegulatoryBody;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegulatoryBodySettingsController extends Controller
{
    /**
     * Get notification preferences (PAGE 9 - Settings & Notifications).
     */
    public function getNotificationPreferences(Request $request): JsonResponse
    {
        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $preferences = $regulatoryBody->notification_preferences ?? $this->getDefaultPreferences();

            return response()->json(['preferences' => $preferences], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update notification preferences (PAGE 9 - Update Settings).
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'blood_stock_alerts' => ['required', 'boolean'],
            'fraud_detection' => ['required', 'boolean'],
            'emergency_requests' => ['required', 'boolean'],
            'event_participation' => ['required', 'boolean'],
            'donor_retention' => ['required', 'boolean'],
            'donor_feedback' => ['required', 'boolean'],
            'donation_drive' => ['required', 'boolean'],
            'emergency_notification' => ['required', 'boolean'],
            'new_donor_registrations' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $regulatoryBody = RegulatoryBody::where('user_id', $request->user()->id)->first();

            if (!$regulatoryBody) {
                return response()->json(['error' => 'Regulatory body not found.'], 404);
            }

            $preferences = [
                'blood_stock_alerts' => $request->blood_stock_alerts,
                'fraud_detection' => $request->fraud_detection,
                'emergency_requests' => $request->emergency_requests,
                'event_participation' => $request->event_participation,
                'donor_retention' => $request->donor_retention,
                'donor_feedback' => $request->donor_feedback,
                'donation_drive' => $request->donation_drive,
                'emergency_notification' => $request->emergency_notification,
                'new_donor_registrations' => $request->new_donor_registrations,
            ];

            $regulatoryBody->update(['notification_preferences' => $preferences]);

            return response()->json([
                'message' => 'Notification preferences updated successfully.',
                'preferences' => $preferences,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get default notification preferences.
     */
    private function getDefaultPreferences(): array
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
