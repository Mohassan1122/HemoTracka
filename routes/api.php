<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\DonorController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\BloodRequestController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\DonorBadgeController;
use App\Http\Controllers\Api\FacilitiesController;
use App\Http\Controllers\Api\BloodBankController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\ActivityFeedController;
use App\Http\Controllers\Api\Mobile\MessageController as MobileMessageController;
use App\Http\Controllers\Api\RegulatoryBodyAuthController;
use App\Http\Controllers\Api\RegulatoryBodyProfileController;
use App\Http\Controllers\Api\RegulatoryBodyDashboardController;
use App\Http\Controllers\Api\RegulatoryBodyComplianceController;
use App\Http\Controllers\Api\RegulatoryBodyBloodBanksController;
use App\Http\Controllers\Api\RegulatoryBodyInventoryController;
use App\Http\Controllers\Api\RegulatoryBodyMessagesController;
use App\Http\Controllers\Api\RegulatoryBodySettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
});

// Regulatory Body Auth Routes (Public)
Route::prefix('auth/regulatory-body')->group(function () {
    Route::post('/register', [RegulatoryBodyAuthController::class, 'register']);
    Route::post('/login', [RegulatoryBodyAuthController::class, 'login']);
});

// Track delivery by tracking code (public)
Route::get('/deliveries/track/{trackingCode}', [DeliveryController::class, 'track']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // =========================================================================
    // 0. REGULATORY BODY ROUTES (/regulatory-body)
    // =========================================================================
    Route::prefix('regulatory-body')->group(function () {
        // Profile Management (PAGE 2)
        Route::get('/profile', [RegulatoryBodyProfileController::class, 'getProfile']);
        Route::put('/profile', [RegulatoryBodyProfileController::class, 'updateProfile']);
        Route::post('/profile/profile-picture', [RegulatoryBodyProfileController::class, 'uploadProfilePicture']);
        Route::post('/profile/cover-picture', [RegulatoryBodyProfileController::class, 'uploadCoverPicture']);

        // Social Connections (PAGE 2)
        Route::post('/social-connections', [RegulatoryBodyProfileController::class, 'addSocialConnection']);
        Route::put('/social-connections/{id}', [RegulatoryBodyProfileController::class, 'updateSocialConnection']);
        Route::delete('/social-connections/{id}', [RegulatoryBodyProfileController::class, 'deleteSocialConnection']);

        // Dashboard (PAGE 3)
        Route::get('/dashboard/stats', [RegulatoryBodyDashboardController::class, 'getStats']);
        Route::get('/dashboard/inventory-chart', [RegulatoryBodyDashboardController::class, 'getInventoryChart']);
        Route::get('/dashboard/donation-trends', [RegulatoryBodyDashboardController::class, 'getDonationTrends']);
        Route::get('/dashboard/recent-donors', [RegulatoryBodyDashboardController::class, 'getRecentDonors']);
        Route::get('/dashboard/recent-requests', [RegulatoryBodyDashboardController::class, 'getRecentRequests']);

        // Compliance Management (PAGE 4)
        Route::get('/compliance/status', [RegulatoryBodyComplianceController::class, 'getComplianceStatus']);
        Route::get('/compliance/donation-trends', [RegulatoryBodyComplianceController::class, 'getDonationTrends']);
        Route::get('/compliance/blood-demand-supply', [RegulatoryBodyComplianceController::class, 'getBloodDemandSupply']);
        Route::get('/compliance/filters/locations', [RegulatoryBodyComplianceController::class, 'getFilterLocations']);
        Route::get('/compliance/filters/blood-types', [RegulatoryBodyComplianceController::class, 'getFilterBloodTypes']);

        // Blood Banks Directory (PAGE 5 & 6)
        Route::get('/blood-banks', [RegulatoryBodyBloodBanksController::class, 'index']);
        Route::get('/blood-banks/{id}', [RegulatoryBodyBloodBanksController::class, 'show']);
        Route::get('/blood-banks/{id}/inventory-chart', [RegulatoryBodyBloodBanksController::class, 'getInventoryChart']);
        Route::get('/blood-banks/{id}/blood-demand-supply', [RegulatoryBodyBloodBanksController::class, 'getBloodDemandSupply']);
        Route::get('/blood-banks/filter/statuses', [RegulatoryBodyBloodBanksController::class, 'getFilterStatuses']);

        // Inventory Management (PAGE 7)
        Route::get('/inventory', [RegulatoryBodyInventoryController::class, 'index']);
        Route::get('/inventory/chart', [RegulatoryBodyInventoryController::class, 'getChart']);
        Route::get('/inventory/export', [RegulatoryBodyInventoryController::class, 'export']);
        Route::get('/inventory/stats', [RegulatoryBodyInventoryController::class, 'getStats']);

        // Messages (PAGE 8)
        Route::get('/messages', [RegulatoryBodyMessagesController::class, 'getConversations']);
        Route::get('/messages/{conversationId}', [RegulatoryBodyMessagesController::class, 'getConversation']);
        Route::post('/messages', [RegulatoryBodyMessagesController::class, 'sendMessage']);
        Route::post('/messages/create-alert', [RegulatoryBodyMessagesController::class, 'createAlert']);
        Route::put('/messages/{id}/read', [RegulatoryBodyMessagesController::class, 'markAsRead']);

        // Settings & Notifications (PAGE 9)
        Route::get('/notification-preferences', [RegulatoryBodySettingsController::class, 'getNotificationPreferences']);
        Route::put('/notification-preferences', [RegulatoryBodySettingsController::class, 'updateNotificationPreferences']);

        // Logout
        Route::post('/logout', [RegulatoryBodyAuthController::class, 'logout']);
    });

    // =========================================================================
    // 1. SHARED ROUTES (Profiles, Messages, Notifications, Feedback)
    // =========================================================================

    // Auth Profile
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // Messages
    Route::prefix('messages')->group(function () {
        Route::get('/inbox', [MessageController::class, 'inbox']);
        Route::get('/sent', [MessageController::class, 'sent']);
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);
        Route::post('/mark-all-read', [MessageController::class, 'markAllRead']);
    });
    Route::apiResource('messages', MessageController::class)->except(['index', 'update']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Feedback
    Route::get('/feedback/average-rating', [FeedbackController::class, 'averageRating']);
    Route::apiResource('feedback', FeedbackController::class);

    // Mobile Activity Feed
    Route::get('/activity-feed', [ActivityFeedController::class, 'index']);

    // Mobile Messaging (New Chat Interface)
    Route::prefix('mobile')->group(function () {
        Route::get('/messages/conversations', [MobileMessageController::class, 'conversations']);
        Route::get('/messages/chat/{otherUser}', [MobileMessageController::class, 'chat']);
        Route::post('/messages/send/{otherUser}', [MobileMessageController::class, 'send']);
    });

    // =========================================================================
    // BLOOD REQUEST ROUTES (Accessible to all authenticated users)
    // =========================================================================
    Route::post('/blood-requests/{bloodRequest}/cancel', [BloodRequestController::class, 'cancel']);
    Route::post('/blood-requests/{bloodRequest}/view', [BloodRequestController::class, 'recordView']);
    Route::apiResource('blood-requests', BloodRequestController::class)->parameters(['blood-requests' => 'bloodRequest']);

    // User Requests (Personal blood requests for the authenticated user)
    Route::prefix('user-requests')->group(function () {
        Route::get('/', [BloodRequestController::class, 'myRequests']);
        Route::get('/stats', [BloodRequestController::class, 'requestStats']);
        Route::post('/{userRequest}/mark-as-read', [BloodRequestController::class, 'markAsRead']);
    });

    // =========================================================================
    // 2. DONOR ROUTES (/donor)
    // =========================================================================
    Route::prefix('donor')->group(function () {
        Route::get('/dashboard', [DonorController::class, 'dashboard']);
        Route::get('/eligibility', [DonorController::class, 'eligibility']);
        Route::get('/donations', [DonorController::class, 'donations']);
        Route::get('/badges', [DonorBadgeController::class, 'donorBadges']);
        Route::post('/check-badges', [DonorBadgeController::class, 'checkAndAward']);

        // Searching Blood Banks
        Route::get('/blood-banks', [OrganizationController::class, 'bloodBanks']);
        Route::get('/blood-banks/nearby', [OrganizationController::class, 'nearby']);

        // Scheduling
        Route::get('/appointments/upcoming', [AppointmentController::class, 'upcoming']);
        Route::apiResource('appointments', AppointmentController::class);
    });

    // =========================================================================
    // 3. FACILITIES ROUTES (/facilities)
    // =========================================================================
    Route::prefix('facilities')->group(function () {
        Route::get('/all', [FacilitiesController::class, 'getAllFacilities']);
        Route::get('/dashboard', [FacilitiesController::class, 'dashboard']);
        Route::get('/request-history', [FacilitiesController::class, 'requestHistory']);
        Route::get('/reports', [FacilitiesController::class, 'reportsOverview']);
        Route::get('/inventory-search', [FacilitiesController::class, 'searchBloodInventory']);

        // Staff Management
        Route::get('/users', [FacilitiesController::class, 'users']);
        Route::post('/users', [FacilitiesController::class, 'addUser']);
        Route::put('/users/{staffMember}', [FacilitiesController::class, 'updateUser']);
        Route::delete('/users/{staffMember}', [FacilitiesController::class, 'deleteUser']);

        // Profile Management
        Route::put('/profile', [FacilitiesController::class, 'updateProfile']);

        // Exports
        Route::get('/requests/export', [FacilitiesController::class, 'exportRequests']);

        // Appointments (Facilitating availability)
        Route::get('/organizations/{organization}/available-slots', [AppointmentController::class, 'availableSlots']);

        // Multi-Offer System
        Route::get('/blood-requests/{bloodRequest}/offers', [OfferController::class, 'index']);
        Route::post('/offers/{offer}/accept', [OfferController::class, 'accept']);
        Route::post('/offers/{offer}/reject', [OfferController::class, 'reject']);

        // Payments
        Route::get('/payments/history', [PaymentController::class, 'index']);
        Route::post('/payments/process', [PaymentController::class, 'store']);

        // Mobile Deliveries Enhancements
        Route::post('/deliveries/{delivery}/confirm-receipt', [DeliveryController::class, 'confirmReceipt']);
        Route::get('/deliveries/{delivery}/timeline', [DeliveryController::class, 'timeline']);
    });

    // =========================================================================
    // 4. BLOOD BANK ROUTES (/blood-bank)
    // =========================================================================
    Route::prefix('blood-bank')->group(function () {
        Route::get('/dashboard', [BloodBankController::class, 'dashboard']);
        Route::get('/inventory', [BloodBankController::class, 'inventory']);
        Route::get('/donations', [BloodBankController::class, 'donations']);
        Route::put('/donors/{id}/health', [BloodBankController::class, 'updateDonorHealth']);
        Route::get('/requests', [BloodBankController::class, 'requests']);
        Route::post('/requests/{id}/accept', [BloodBankController::class, 'acceptRequest']);
        Route::get('/deliveries', [BloodBankController::class, 'deliveries']);
        Route::post('/requests/{id}/confirm-delivery', [BloodBankController::class, 'confirmDelivery']);
        Route::put('/deliveries/{id}/status', [BloodBankController::class, 'updateDeliveryStatus']);
        Route::match(['get', 'put'], '/settings', [BloodBankController::class, 'settings']);
        Route::put('/profile', [BloodBankController::class, 'updateProfile']);

        // My Requests (Organization-specific blood requests)
        Route::get('/my-requests', [BloodBankController::class, 'myRequests']);
        Route::get('/my-requests/stats', [BloodBankController::class, 'requestStats']);
        Route::post('/my-requests/{id}/mark-as-read', [BloodBankController::class, 'markRequestAsRead']);

        // Inventory management
        Route::get('/inventory/summary', [InventoryController::class, 'summary']);
        Route::post('/inventory/{inventoryItem}/adjust-stock', [InventoryController::class, 'adjustStock']);
        Route::apiResource('inventory', InventoryController::class)->parameters(['inventory' => 'inventoryItem']);

        // Request approval
        Route::post('/blood-requests/{bloodRequest}/approve', [BloodRequestController::class, 'approve']);

        // Multi-Offer system (Blood Bank side)
        Route::post('/blood-requests/{bloodRequest}/submit-offer', [OfferController::class, 'store']);
    });

    // Subscriptions (Available to all authenticated users)
    Route::prefix('subscriptions')->group(function () {
        Route::get('/plans', [SubscriptionController::class, 'index']);
        Route::get('/current', [SubscriptionController::class, 'current']);
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    });

    // =========================================================================
    // 5. RIDER ROUTES (/rider)
    // =========================================================================
    Route::prefix('rider')->group(function () {
        Route::get('/available', [RiderController::class, 'available']);
        Route::post('/update-location', [RiderController::class, 'updateLocation']);

        // Delivery tracking
        Route::get('/deliveries', [DeliveryController::class, 'index']);
        Route::post('/deliveries/{delivery}/pick-up', [DeliveryController::class, 'pickUp']);
        Route::post('/deliveries/{delivery}/in-transit', [DeliveryController::class, 'inTransit']);
        Route::post('/deliveries/{delivery}/complete', [DeliveryController::class, 'complete']);
        Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show']);
    });

    // =========================================================================
    // 6. ADMIN ROUTES (/admin)
    // =========================================================================
    Route::prefix('admin')->group(function () {
        // Platform Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // Organization Management
        Route::get('/organizations', [AdminController::class, 'organizations']);
        Route::put('/organizations/{id}/status', [AdminController::class, 'updateOrganizationStatus']);
        Route::apiResource('organizations', OrganizationController::class)->except(['index']); // Keep CRUD, but prefix is /admin/organizations

        // User & Donor Management
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/donors', [AdminController::class, 'donors']); // I'll add this to controller
        Route::apiResource('donors', DonorController::class)->except(['index']);

        // Logistics & Riders
        Route::get('/logistics', [AdminController::class, 'logistics']);
        Route::apiResource('riders', RiderController::class);

        // Feedbacks & Support
        Route::get('/feedback', [AdminController::class, 'feedback']);

        // Stats & Reports (Legacy/Detailed)
        Route::get('/stats/donations-by-blood-group', [StatsController::class, 'donationsByBloodGroup']);
        Route::get('/stats/inventory-by-blood-group', [StatsController::class, 'inventoryByBloodGroup']);
        Route::get('/stats/requests-by-status', [StatsController::class, 'requestsByStatus']);
        Route::get('/stats/monthly-donations', [StatsController::class, 'monthlyDonations']);
        Route::get('/stats/low-stock-alerts', [StatsController::class, 'lowStockAlerts']);

        // Other Global Resources
        Route::apiResource('donations', DonationController::class);
        Route::apiResource('badges', DonorBadgeController::class);
        Route::apiResource('deliveries', DeliveryController::class);
    });
});

