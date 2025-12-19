<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\Donor;
use App\Models\Rider;
use App\Models\InventoryItem;
use App\Models\Donation;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Super Admin
        User::updateOrCreate(
            ['email' => 'admin@hemotracka.com'],
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'phone' => '09000000001',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create multiple organizations
        $orgs = [
            ['name' => 'Lagos State Hospital', 'type' => 'Hospital', 'status' => 'Active'],
            ['name' => 'Abuja Blood Center', 'type' => 'Blood Bank', 'status' => 'Active'],
            ['name' => 'Kano Medical', 'type' => 'Hospital', 'status' => 'Pending'],
            ['name' => 'Port Harcourt Bank', 'type' => 'Blood Bank', 'status' => 'Suspended'],
        ];

        foreach ($orgs as $orgData) {
            $org = Organization::updateOrCreate(
                ['license_number' => 'LIC-ADMIN-' . strtoupper(str_replace(' ', '-', $orgData['name']))],
                array_merge($orgData, [
                    'address' => 'Admin Seed Location',
                    'contact_email' => 'contact@' . strtolower(str_replace(' ', '', $orgData['name'])) . '.com',
                    'phone' => '090' . rand(10000000, 99999999),
                ])
            );

            // Add some inventory to Blood Banks
            if ($orgData['type'] === 'Blood Bank') {
                foreach (['A+', 'O-', 'B+'] as $group) {
                    InventoryItem::updateOrCreate(
                        [
                            'organization_id' => $org->id,
                            'blood_group' => $group,
                            'type' => 'Whole Blood'
                        ],
                        [
                            'units_in_stock' => rand(20, 100),
                            'threshold' => 10,
                            'location' => 'Main Shelf',
                            'expiry_date' => now()->addMonths(3),
                        ]
                    );
                }
            }
        }

        // 3. Create platform-wide donors
        $activeOrg = Organization::where('status', 'Active')->first();
        for ($i = 0; $i < 5; $i++) {
            $firstName = 'Donor';
            $lastName = 'Seed' . $i;
            $email = "donor_seed{$i}@example.com";
            $phone = '081' . rand(10000000, 99999999);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'password' => Hash::make('password'),
                    'role' => 'donor',
                ]
            );

            $donor = Donor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'organization_id' => $activeOrg->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'blood_group' => ['A+', 'O-', 'B+', 'AB+'][rand(0, 3)],
                    'genotype' => 'AA',
                    'date_of_birth' => '1990-01-01',
                    'status' => 'Eligible',
                ]
            );

            // Add a donation
            Donation::updateOrCreate(
                [
                    'donor_id' => $donor->id,
                    'donation_date' => now()->startOfDay() // Simplified for check
                ],
                [
                    'organization_id' => $activeOrg->id,
                    'blood_group' => $donor->blood_group,
                    'units' => 1,
                    'status' => 'Stored',
                ]
            );
        }

        // 4. Create some Riders
        for ($i = 0; $i < 3; $i++) {
            $email = "rider_seed{$i}@example.com";
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => 'Rider',
                    'last_name' => 'Seed' . $i,
                    'phone' => '070' . rand(10000000, 99999999),
                    'password' => Hash::make('password'),
                    'role' => 'rider',
                ]
            );

            Rider::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => 'Bike',
                    'vehicle_plate' => 'RIDER-' . $i,
                    'status' => $i === 0 ? 'Available' : 'Offline',
                ]
            );
        }
    }
}
