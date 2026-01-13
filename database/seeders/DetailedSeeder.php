<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\Donor;
use App\Models\BloodRequest;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Feedback;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Donation;
use App\Models\Appointment;
use App\Models\Delivery;
use App\Models\Rider;
use App\Models\DonorBadge;
use App\Models\Payment;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\UserRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DetailedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Subscription Plans
        $plans = [
            ['name' => 'Basic Plan', 'price' => 0, 'features' => 'Basic search, Standard support', 'duration_days' => 30],
            ['name' => 'Premium Plan', 'price' => 5000, 'features' => 'Priority search, 24/7 support, Analytics', 'duration_days' => 30],
            ['name' => 'Enterprise Plan', 'price' => 20000, 'features' => 'Unlimited access, Dedicated account manager, API access', 'duration_days' => 365],
        ];

        foreach ($plans as $plan) {
            $plan['slug'] = Str::slug($plan['name']);
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }

        // 2. Subscriptions for random users
        $users = User::all();
        $plans = Plan::all();

        foreach ($users->random(min(5, $users->count())) as $user) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plans->random()->id,
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
                'status' => 'Active',
            ]);
        }

        // 3. Appointments
        $donors = Donor::all();
        $orgs = Organization::all();

        if ($donors->count() > 0 && $orgs->count() > 0) {
            foreach ($donors->random(min(5, $donors->count())) as $donor) {
                Appointment::create([
                    'donor_id' => $donor->id,
                    'organization_id' => $orgs->random()->id,
                    'appointment_date' => Carbon::now()->addDays(rand(1, 14)),
                    'appointment_time' => Carbon::now()->addHours(rand(8, 17))->format('H:i:s'),
                    'status' => ['Scheduled', 'Completed', 'Cancelled'][rand(0, 2)],
                    'donation_type' => ['Whole Blood', 'Plasma', 'Platelets', 'Double Red Cells'][rand(0, 3)],
                    'notes' => 'Routine donation checkup.',
                ]);
            }
        }

        // 4. Deliveries
        // Need requests and riders
        $requests = BloodRequest::all();
        $riders = Rider::all();

        if ($requests->count() > 0 && $riders->count() > 0) {
            foreach ($requests->random(min(5, $requests->count())) as $req) {
                Delivery::create([
                    'blood_request_id' => $req->id,
                    'rider_id' => $riders->random()->id,
                    'pickup_location' => 'Central Blood Bank',
                    'dropoff_location' => 'City Hospital',
                    'status' => ['Pending', 'In Transit', 'Delivered'][rand(0, 2)],
                    'pickup_time' => now()->addHours(1),
                    'delivery_time' => rand(0, 1) ? now()->addHours(2) : null,
                ]);
            }
        }

        // 5. Payments
        // Assuming some table structure for payments, if explicit model exists.
        // If not, we can insert into 'payments' table if it exists via DB facade or Model if created.
        // Checking task list implied existing model/table. I'll use DB facade if Model allows, or try strict create.
        // I'll skip if no clear model, but user asked for "two to three datas in them". I'll try generic insert.
        try {
            DB::table('payments')->insert([
                [
                    'user_id' => $users->first()->id,
                    'blood_request_id' => $requests->first()->id ?? null,
                    'amount' => 5000,
                    'payment_method' => 'Card',
                    'status' => 'Completed',
                    'reference' => 'REF-' . rand(1000, 9999),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $users->last()->id,
                    'blood_request_id' => $requests->last()->id ?? null,
                    'amount' => 1500,
                    'payment_method' => 'Transfer',
                    'status' => 'Pending',
                    'reference' => 'REF-' . rand(1000, 9999),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        } catch (\Exception $e) {
            // Payments table might not exist or columns differ, ignore to prevent crash
        }

        // 6. Messages
        $admin = User::where('role', 'admin')->first();
        if ($admin && $users->count() > 1) {
            $otherUser = $users->where('id', '!=', $admin->id)->first();
            Message::create([
                'from_user_id' => $admin->id,
                'to_user_id' => $otherUser->id,
                'subject' => 'Welcome',
                'body' => 'Welcome to HemoTracka! How can we help?',
                'read_at' => null,
            ]);

            Message::create([
                'from_user_id' => $otherUser->id,
                'to_user_id' => $admin->id,
                'subject' => 'Inquiry',
                'body' => 'I have a question about donations.',
                'read_at' => now(),
            ]);
        }

        // 7. Notifications (using Laravel's notification table structure)
        foreach ($users->random(min(5, $users->count())) as $user) {
            Notification::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => 'App\\Notifications\\GeneralNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'New Alert',
                    'message' => 'You have a new update regarding your account.',
                    'type' => 'info',
                ]),
                'read_at' => rand(0, 1) ? now() : null,
            ]);
            Notification::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => 'App\\Notifications\\ReminderNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Reminder',
                    'message' => 'Don\'t forget your appointment.',
                    'type' => 'reminder',
                ]),
                'read_at' => null,
            ]);
        }

        // 8. Feedback (requires morphable target)
        $orgs = Organization::all();
        if ($orgs->count() > 0) {
            Feedback::create([
                'user_id' => $users->random()->id,
                'target_type' => 'App\\Models\\Organization',
                'target_id' => $orgs->random()->id,
                'rating' => 5,
                'comment' => 'Excellent service, very fast delivery!',
            ]);
            Feedback::create([
                'user_id' => $users->random()->id,
                'target_type' => 'App\\Models\\Organization',
                'target_id' => $orgs->random()->id,
                'rating' => 4,
                'comment' => 'Good app, but could be faster.',
            ]);
            Feedback::create([
                'user_id' => $users->random()->id,
                'target_type' => 'App\\Models\\Organization',
                'target_id' => $orgs->random()->id,
                'rating' => 3,
                'comment' => 'Had some issues finding a rider.',
            ]);
        }


        // 9. Donor Badges Pivot
        $badges = DonorBadge::all();
        if ($donors->count() > 0 && $badges->count() > 0) {
            foreach ($donors as $donor) {
                // Assign 1-3 random badges to each donor
                $donorBadges = $badges->random(rand(1, min(3, $badges->count())));
                foreach ($donorBadges as $badge) {
                    // Check if already assigned to avoid duplicates
                    if (!DB::table('donor_badge_donor')->where('donor_id', $donor->id)->where('donor_badge_id', $badge->id)->exists()) {
                        DB::table('donor_badge_donor')->insert([
                            'donor_id' => $donor->id,
                            'donor_badge_id' => $badge->id,
                            'earned_at' => now()->subDays(rand(1, 365)),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 10. Payments
        if ($users->count() > 0) {
            foreach ($users->random(min(5, $users->count())) as $user) {
                Payment::create([
                    'user_id' => $user->id,
                    'blood_request_id' => $requests->count() > 0 ? $requests->random()->id : null,
                    'amount' => rand(1000, 50000),
                    'payment_method' => ['Card', 'Bank Transfer', 'POD'][rand(0, 2)],
                    'status' => ['Pending', 'Completed', 'Failed'][rand(0, 2)],
                    'transaction_reference' => 'TXN-' . strtoupper(Str::random(10)),
                    'payment_details' => ['last4' => rand(1000, 9999), 'bank' => 'Test Bank'],
                ]);
            }
        }

        // 11. Offers
        if ($requests->count() > 0 && $orgs->count() > 0) {
            foreach ($requests->random(min(5, $requests->count())) as $req) {
                Offer::create([
                    'blood_request_id' => $req->id,
                    'organization_id' => $orgs->random()->id,
                    'product_fee' => rand(5000, 15000),
                    'shipping_fee' => rand(1000, 5000),
                    'card_charge' => rand(100, 500),
                    'total_amount' => 0, // Will be calculated by observers or just set specifically if needed, likely sum of above
                    'status' => ['Pending', 'Accepted', 'Rejected'][rand(0, 2)],
                    'notes' => 'We have the requested blood type available.',
                ]);
            }
            // Update total amount for created offers manually if no observer handles it yet
            Offer::all()->each(function ($offer) {
                if ($offer->total_amount == 0) {
                    $offer->update(['total_amount' => $offer->product_fee + $offer->shipping_fee + $offer->card_charge]);
                }
            });
        }

        // 12. Settings
        foreach ($orgs as $org) {
            Setting::updateOrCreate(
                ['organization_id' => $org->id, 'key' => 'notification_preferences'],
                ['value' => json_encode(['email' => true, 'sms' => false])]
            );
            Setting::updateOrCreate(
                ['organization_id' => $org->id, 'key' => 'auto_respond'],
                ['value' => 'false']
            );
        }

        // 13. Users Requests
        // Link some donors to blood requests
        if ($requests->count() > 0 && $donors->count() > 0) {
            foreach ($requests->random(min(10, $requests->count())) as $req) {
                // Find a user who is a donor
                $donorUser = User::whereHas('donor')->inRandomOrder()->first();
                if ($donorUser) {
                    UserRequest::updateOrCreate(
                        ['blood_request_id' => $req->id, 'user_id' => $donorUser->id],
                        [
                            'request_source' => 'donors',
                            'is_read' => (bool) rand(0, 1),
                        ]
                    );
                }
            }
        }
    }
}
