<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\User;
use App\Models\Donor;
use App\Models\Donation;
use App\Models\InventoryItem;
use App\Models\BloodRequest;
use App\Models\Rider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BloodBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // 1. Create a Blood Bank Organization
            $bloodBank = Organization::updateOrCreate(
                ['license_number' => 'BB-778899'],
                [
                    'name' => 'Central Blood Bank Port Harcourt',
                    'type' => 'Blood Bank',
                    'address' => 'No 15 Aggrey Road, Port Harcourt',
                    'contact_email' => 'info@phbloodbank.com',
                    'phone' => '234812343002',
                    'status' => 'Active',
                    'facebook_link' => 'https://facebook.com/phbloodbank',
                    'twitter_link' => 'https://twitter.com/phbloodbank',
                    'instagram_link' => 'https://instagram.com/phbloodbank',
                    'linkedin_link' => 'https://linkedin.com/company/phbloodbank',
                ]
            );

            // 2. Create a Blood Bank Admin User
            $bbAdmin = User::updateOrCreate(
                ['email' => 'admin@phbloodbank.com'],
                [
                    'first_name' => 'Dayo',
                    'last_name' => 'Kingsley',
                    'password' => Hash::make('password'),
                    'role' => 'blood_banks',
                    'phone' => '234812343009',
                    'organization_id' => $bloodBank->id,
                    'email_verified_at' => now(),
                ]
            );

            // 3. Create a Hospital for requests
            $hospital = Organization::updateOrCreate(
                ['license_number' => 'HOSP-112233'],
                [
                    'name' => 'BMH Hospital',
                    'type' => 'Hospital',
                    'address' => 'BMH Road, Port Harcourt',
                    'contact_email' => 'admin@bmh.com',
                    'phone' => '234812343003',
                    'status' => 'Active',
                ]
            );

            // 4. Create some Donors for this bank
            $donors = [
                ['first_name' => 'Abayomi', 'last_name' => 'Ayodele', 'blood_group' => 'A-', 'genotype' => 'AA', 'height' => '177cm'],
                ['first_name' => 'Caleb', 'last_name' => 'Oko Jumbo', 'blood_group' => 'B+', 'genotype' => 'AS', 'height' => '180cm'],
                ['first_name' => 'Matthew', 'last_name' => 'Prince', 'blood_group' => 'O+', 'genotype' => 'AA', 'height' => '175cm'],
            ];

            foreach ($donors as $donorData) {
                $donor = Donor::updateOrCreate(
                    ['first_name' => $donorData['first_name'], 'last_name' => $donorData['last_name']],
                    array_merge($donorData, [
                        'organization_id' => $bloodBank->id,
                        'phone' => '080' . rand(10000000, 99999999),
                        'date_of_birth' => Carbon::now()->subYears(rand(18, 50)),
                        'status' => 'Eligible',
                    ])
                );

                // Add a donation record for each donor
                Donation::updateOrCreate(
                    [
                        'donor_id' => $donor->id,
                        'donation_date' => Carbon::now()->startOfDay()
                    ],
                    [
                        'organization_id' => $bloodBank->id,
                        'blood_group' => $donor->blood_group,
                        'units' => 2,
                        'platelets_type' => 'Single Donor',
                        'doctor_notes' => 'Health check normal. Donation smooth.',
                        'status' => 'Stored',
                    ]
                );
            }

            // 5. Populate Inventory
            $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            foreach ($bloodGroups as $group) {
                InventoryItem::updateOrCreate(
                    ['organization_id' => $bloodBank->id, 'blood_group' => $group, 'type' => 'Whole Blood'],
                    [
                        'units_in_stock' => rand(10, 50),
                        'threshold' => 10,
                        'expiry_date' => now()->addMonths(6),
                    ]
                );

                InventoryItem::updateOrCreate(
                    ['organization_id' => $bloodBank->id, 'blood_group' => $group, 'type' => 'Platelets'],
                    [
                        'units_in_stock' => rand(5, 20),
                        'threshold' => 5,
                        'expiry_date' => now()->addDays(5),
                    ]
                );
            }

            // 6. Create some Blood Requests
            $request1 = BloodRequest::updateOrCreate(
                ['patient_name' => 'Dayo Kingsley', 'organization_id' => $hospital->id],
                [
                    'blood_group' => 'A-',
                    'units_needed' => 10,
                    'hospital_unit' => 'Emergency',
                    'source_type' => 'Hospital',
                    'type' => 'Routine',
                    'urgency_level' => 'Normal',
                    'needed_by' => now()->addHours(2),
                    'status' => 'Pending',
                    'notes' => 'Patient in critical condition',
                ]
            );

            // 7. Create a Rider
            $riderUser = User::updateOrCreate(
                ['email' => 'rider1@hemotracka.com'],
                [
                    'first_name' => 'Fast',
                    'last_name' => 'Delivery Rider',
                    'password' => Hash::make('password'),
                    'phone' => '234812343007',
                    'role' => 'rider',
                    'email_verified_at' => now(),
                ]
            );

            Rider::updateOrCreate(
                ['user_id' => $riderUser->id],
                [
                    'vehicle_plate' => 'PH-9900',
                    'vehicle_type' => 'Bike',
                    'status' => 'Available',
                ]
            );
        } catch (\Exception $e) {
            die("SEEDER_ERROR: " . $e->getMessage());
        }
    }
}
