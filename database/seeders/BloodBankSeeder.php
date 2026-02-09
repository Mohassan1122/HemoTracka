<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\User;
use App\Models\Donor;
use App\Models\Donation;
use App\Models\InventoryItem;
use App\Models\BloodRequest;
use App\Models\OrganizationRequest;
use App\Models\UserRequest;
use App\Models\Rider;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class BloodBankSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Blood Bank User Accounts
        $bloodBankUser1 = User::updateOrCreate(
            ['email' => 'admin@phbloodbank.com'],
            [
                'first_name' => 'Port Harcourt',
                'last_name' => 'Blood Bank',
                'password' => Hash::make('password'),
                'phone' => '234812343002',
                'role' => 'blood_banks',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        $bloodBankUser2 = User::updateOrCreate(
            ['email' => 'admin@naiabloodbank.com'],
            [
                'first_name' => 'NAIA',
                'last_name' => 'Blood Bank',
                'password' => Hash::make('password'),
                'phone' => '234812343004',
                'role' => 'blood_banks',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Blood Bank Organizations (linked to users)
        $bloodBank1 = Organization::updateOrCreate(
            ['license_number' => 'BB-778899'],
            [
                'user_id' => $bloodBankUser1->id,
                'name' => 'Central Blood Bank Port Harcourt',
                'type' => 'Blood Bank',
                'role' => 'blood_banks',
                'address' => 'No 15 Aggrey Road, Port Harcourt',
                'contact_email' => 'info@phbloodbank.com',
                'email' => 'admin@phbloodbank.com',
                'password' => Hash::make('password'),
                'phone' => '234812343002',
                'status' => 'Active',
                'latitude' => 4.8156,
                'longitude' => 7.0498,
                'facebook_link' => 'https://facebook.com/phbloodbank',
                'twitter_link' => 'https://twitter.com/phbloodbank',
                'instagram_link' => 'https://instagram.com/phbloodbank',
                'linkedin_link' => 'https://linkedin.com/company/phbloodbank',
                'description' => 'Leading blood bank in Port Harcourt providing blood services',
                'services' => json_encode(['Blood Banking', 'Testing', 'Storage']),
            ]
        );

        $bloodBank2 = Organization::updateOrCreate(
            ['license_number' => 'BB-445566'],
            [
                'user_id' => $bloodBankUser2->id,
                'name' => 'NAIA Blood Bank',
                'type' => 'Blood Bank',
                'role' => 'blood_banks',
                'address' => '456 Second St',
                'contact_email' => 'info@naiabloodbank.com',
                'email' => 'admin@naiabloodbank.com',
                'password' => Hash::make('password'),
                'phone' => '234812343004',
                'status' => 'Active',
                'latitude' => 9.0820,
                'longitude' => 8.6753,
                'description' => 'Reliable blood bank services',
                'services' => json_encode(['Blood Banking', 'Emergency Services']),
            ]
        );

        // 3. Create Hospital User Account
        $hospitalUser = User::updateOrCreate(
            ['email' => 'admin@bmh.com'],
            [
                'first_name' => 'BMH',
                'last_name' => 'Hospital',
                'password' => Hash::make('password'),
                'phone' => '234812343003',
                'role' => 'facilities',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        // 4. Create Hospital Organization (linked to user)
        $hospital = Organization::updateOrCreate(
            ['license_number' => 'HOSP-112233'],
            [
                'user_id' => $hospitalUser->id,
                'name' => 'BMH Hospital',
                'type' => 'Hospital',
                'role' => 'facilities',
                'address' => 'BMH Road, Port Harcourt',
                'contact_email' => 'admin@bmh.com',
                'email' => 'admin@bmh.com',
                'password' => Hash::make('password'),
                'phone' => '234812343003',
                'status' => 'Active',
                'latitude' => 4.8000,
                'longitude' => 7.0000,
                'description' => 'Full service hospital',
                'services' => json_encode(['Emergency', 'Surgery', 'ICU']),
            ]
        );

        // 5. Create Donor User Accounts
        $donorData = [
            ['first_name' => 'Abayomi', 'last_name' => 'Ayodele', 'blood_group' => 'A-', 'genotype' => 'AA'],
            ['first_name' => 'Caleb', 'last_name' => 'Oko Jumbo', 'blood_group' => 'B+', 'genotype' => 'AS'],
            ['first_name' => 'Matthew', 'last_name' => 'Prince', 'blood_group' => 'O+', 'genotype' => 'AA'],
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'blood_group' => 'O-', 'genotype' => 'AA'],
            ['first_name' => 'David', 'last_name' => 'Williams', 'blood_group' => 'AB+', 'genotype' => 'AA'],
        ];

        foreach ($donorData as $data) {
            $user = User::updateOrCreate(
                ['email' => strtolower($data['first_name'] . '.' . $data['last_name']) . '@donor.com'],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'password' => Hash::make('password'),
                    'phone' => '080' . rand(10000000, 99999999),
                    'role' => 'donor',
                    'gender' => ['Male', 'Female'][rand(0, 1)],
                    'date_of_birth' => Carbon::now()->subYears(rand(18, 50)),
                    'email_verified_at' => now(),
                ]
            );

            // Create Donor profile linked to user
            $donor = Donor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'blood_group' => $data['blood_group'],
                    'genotype' => $data['genotype'],
                    'phone' => $user->phone,
                    'date_of_birth' => $user->date_of_birth,
                    'status' => 'Eligible',
                    'height' => rand(160, 190) . 'cm',
                ]
            );

            // Add donation record
            Donation::updateOrCreate(
                [
                    'donor_id' => $donor->id,
                    'donation_date' => Carbon::now()->subDays(rand(1, 30))->startOfDay()
                ],
                [
                    'organization_id' => $bloodBank1->id,
                    'blood_group' => $donor->blood_group,
                    'units' => 2,
                    'doctor_notes' => 'Health check normal. Donation smooth.',
                    'status' => 'Stored',
                ]
            );
        }

        // 6. Populate Inventory for both blood banks
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        foreach ([$bloodBank1, $bloodBank2] as $bank) {
            foreach ($bloodGroups as $group) {
                InventoryItem::updateOrCreate(
                    ['organization_id' => $bank->id, 'blood_group' => $group, 'type' => 'Whole Blood'],
                    [
                        'units_in_stock' => rand(10, 50),
                        'threshold' => 10,
                        'expiry_date' => now()->addMonths(6),
                    ]
                );

                InventoryItem::updateOrCreate(
                    ['organization_id' => $bank->id, 'blood_group' => $group, 'type' => 'Platelets'],
                    [
                        'units_in_stock' => rand(5, 20),
                        'threshold' => 5,
                        'expiry_date' => now()->addDays(5),
                    ]
                );
            }
        }

        // 7. Create Blood Requests with Distribution

        // Request 1: From Hospital to Blood Banks
        $request1 = BloodRequest::updateOrCreate(
            ['patient_name' => 'Dayo Kingsley', 'organization_id' => $hospital->id],
            [
                'blood_group' => 'O-',
                'genotype' => 'AA',
                'units_needed' => 4,
                'type' => 'Blood',
                'request_source' => 'blood_banks',
                'urgency_level' => 'Normal',
                'needed_by' => now()->addDays(2),
                'status' => 'Pending',
                'notes' => 'Patient needs O- blood for surgery',
            ]
        );

        // Distribute to all blood banks
        $allBloodBanks = Organization::where('role', 'blood_banks')
            ->where('status', 'Active')
            ->where('id', '!=', $request1->organization_id)
            ->get();

        foreach ($allBloodBanks as $bb) {
            OrganizationRequest::updateOrCreate(
                ['blood_request_id' => $request1->id, 'organization_id' => $bb->id],
                ['request_source' => 'blood_banks', 'status' => 'Pending', 'is_read' => false]
            );
        }

        // Request 2: From Blood Bank to Donors
        $request2 = BloodRequest::updateOrCreate(
            ['patient_name' => 'Emergency Patient A', 'organization_id' => $bloodBank1->id],
            [
                'blood_group' => 'A-',
                'genotype' => 'AA',
                'units_needed' => 3,
                'type' => 'Blood',
                'request_source' => 'donors',
                'urgency_level' => 'Critical',
                'needed_by' => now()->addHours(12),
                'status' => 'Pending',
                'notes' => 'Urgent need for A- donors',
            ]
        );

        // Distribute to all donor users
        $allDonors = User::where('role', 'donor')->get();
        foreach ($allDonors as $donor) {
            UserRequest::updateOrCreate(
                ['blood_request_id' => $request2->id, 'user_id' => $donor->id],
                ['request_source' => 'donors', 'is_read' => false, 'status' => 'Pending']
            );
        }

        // Request 3: To Both Blood Banks AND Donors
        $request3 = BloodRequest::updateOrCreate(
            ['patient_name' => 'Critical Patient B', 'organization_id' => $hospital->id],
            [
                'blood_group' => 'AB+',
                'genotype' => 'AS',
                'units_needed' => 6,
                'type' => 'Blood',
                'request_source' => 'both',
                'urgency_level' => 'Critical',
                'needed_by' => now()->addHours(6),
                'status' => 'Pending',
                'notes' => 'Critical emergency - need AB+ immediately',
            ]
        );

        // Distribute to blood banks
        foreach ($allBloodBanks as $bb) {
            OrganizationRequest::updateOrCreate(
                ['blood_request_id' => $request3->id, 'organization_id' => $bb->id],
                ['request_source' => 'both', 'status' => 'Pending', 'is_read' => false]
            );
        }

        // Distribute to donors
        foreach ($allDonors as $donor) {
            UserRequest::updateOrCreate(
                ['blood_request_id' => $request3->id, 'user_id' => $donor->id],
                ['request_source' => 'both', 'is_read' => false, 'status' => 'Pending']
            );
        }

        // 8. Create a Rider User and Rider Profile
        $riderUser = User::updateOrCreate(
            ['email' => 'rider1@hemotracka.com'],
            [
                'first_name' => 'Fast',
                'last_name' => 'Delivery Rider',
                'password' => Hash::make('password'),
                'phone' => '234812343007',
                'role' => 'rider',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        Rider::updateOrCreate(
            ['user_id' => $riderUser->id],
            [
                'vehicle_type' => 'Bike',
                'vehicle_plate' => 'PH-9900',
                'status' => 'Available',
            ]
        );

        $this->command->info('✅ BloodBankSeeder completed with proper user structure!');
    }
}
