<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\BloodRequest;
use App\Models\OrganizationRequest;
use App\Models\UserRequest;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FacilitiesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Hospital User Accounts
        $hospitalUser1 = User::updateOrCreate(
            ['email' => 'admin@kujehospital.gov.ng'],
            [
                'first_name' => 'Kuje',
                'last_name' => 'National Hospital',
                'password' => Hash::make('password'),
                'phone' => '08012345678',
                'role' => 'facilities',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        $hospitalUser2 = User::updateOrCreate(
            ['email' => 'admin@lth.gov.ng'],
            [
                'first_name' => 'Lagos',
                'last_name' => 'Teaching Hospital',
                'password' => Hash::make('password'),
                'phone' => '08033334444',
                'role' => 'facilities',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Hospital Organizations (linked to users)
        $hospital1 = Organization::updateOrCreate(
            ['license_number' => 'HOSP-2025-001'],
            [
                'user_id' => $hospitalUser1->id,
                'name' => 'Kuje National Hospital',
                'type' => 'Hospital',
                'role' => 'facilities',
                'contact_email' => 'contact@kujehospital.gov.ng',
                'email' => 'admin@kujehospital.gov.ng',
                'password' => Hash::make('password'),
                'phone' => '08012345678',
                'address' => '123 Hospital Road, Kuje, Abuja',
                'status' => 'Active',
                'latitude' => 8.8875,
                'longitude' => 7.2285,
                'description' => 'National referral hospital',
                'services' => json_encode(['Emergency', 'ICU', 'Surgery', 'Maternity']),
            ]
        );

        $hospital2 = Organization::updateOrCreate(
            ['license_number' => 'HOSP-2025-002'],
            [
                'user_id' => $hospitalUser2->id,
                'name' => 'Lagos Teaching Hospital',
                'type' => 'Hospital',
                'role' => 'facilities',
                'contact_email' => 'contact@lth.gov.ng',
                'email' => 'admin@lth.gov.ng',
                'password' => Hash::make('password'),
                'phone' => '08033334444',
                'address' => '1 Lagos Hospital Rd, Ikeja',
                'status' => 'Active',
                'latitude' => 6.5927,
                'longitude' => 3.3743,
                'description' => 'Premier teaching hospital',
                'services' => json_encode(['Teaching', 'Research', 'Specialist Care']),
            ]
        );

        // 3. Create Blood Bank User Accounts
        $bloodBankUser1 = User::updateOrCreate(
            ['email' => 'info@centralbloodbank.org'],
            [
                'first_name' => 'Central',
                'last_name' => 'Blood Bank Abuja',
                'password' => Hash::make('password'),
                'phone' => '08022223333',
                'role' => 'blood_banks',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        $bloodBankUser2 = User::updateOrCreate(
            ['email' => 'admin@lagosbloodbank.org'],
            [
                'first_name' => 'Lagos',
                'last_name' => 'State Blood Bank',
                'password' => Hash::make('password'),
                'phone' => '08044445555',
                'role' => 'blood_banks',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        // 4. Create Blood Bank Organizations (linked to users)
        $bloodBank1 = Organization::updateOrCreate(
            ['license_number' => 'BB-2025-001'],
            [
                'user_id' => $bloodBankUser1->id,
                'name' => 'Central Blood Bank Abuja',
                'type' => 'Blood Bank',
                'role' => 'blood_banks',
                'contact_email' => 'info@centralbloodbank.org',
                'email' => 'info@centralbloodbank.org',
                'password' => Hash::make('password'),
                'phone' => '08022223333',
                'address' => 'Garki District, Abuja',
                'status' => 'Active',
                'latitude' => 9.0765,
                'longitude' => 7.3985,
                'description' => 'Central blood banking facility',
                'services' => json_encode(['Blood Banking', 'Testing', 'Storage', 'Distribution']),
            ]
        );

        $bloodBank2 = Organization::updateOrCreate(
            ['license_number' => 'BB-2025-002'],
            [
                'user_id' => $bloodBankUser2->id,
                'name' => 'Lagos State Blood Bank',
                'type' => 'Blood Bank',
                'role' => 'blood_banks',
                'contact_email' => 'info@lagosbloodbank.org',
                'email' => 'admin@lagosbloodbank.org',
                'password' => Hash::make('password'),
                'phone' => '08044445555',
                'address' => '45 Marina, Lagos Island',
                'status' => 'Active',
                'latitude' => 6.4281,
                'longitude' => 3.4219,
                'description' => 'State blood bank services',
                'services' => json_encode(['Blood Banking', 'Emergency Services']),
            ]
        );

        // 5. Seed inventory for blood banks
        $bloodGroups = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
        foreach ([$bloodBank1, $bloodBank2] as $bank) {
            foreach ($bloodGroups as $group) {
                InventoryItem::updateOrCreate(
                    ['organization_id' => $bank->id, 'blood_group' => $group, 'type' => 'Whole Blood'],
                    [
                        'units_in_stock' => rand(10, 50),
                        'threshold' => 5,
                        'expiry_date' => now()->addDays(rand(5, 30)),
                    ]
                );
            }
        }

        // 6. Create Varied Blood Requests with Distribution
        $requestConfigs = [
            [
                'organization' => $hospital1,
                'patient_name' => 'Patient ' . rand(100, 199),
                'blood_group' => 'O+',
                'genotype' => 'AA',
                'units_needed' => 5,
                'type' => 'Blood',
                'request_source' => 'blood_banks',
                'urgency_level' => 'Critical',
                'needed_by' => now()->addHours(6),
            ],
            [
                'organization' => $hospital1,
                'patient_name' => 'Patient ' . rand(200, 299),
                'blood_group' => 'A-',
                'genotype' => 'AS',
                'units_needed' => 2,
                'type' => 'Platelets',
                'request_source' => 'donors',
                'urgency_level' => 'High',
                'needed_by' => now()->addHours(12),
            ],
            [
                'organization' => $hospital2,
                'patient_name' => 'Patient ' . rand(300, 399),
                'blood_group' => 'B+',
                'genotype' => 'AA',
                'units_needed' => 3,
                'type' => 'Blood',
                'request_source' => 'both',
                'urgency_level' => 'Normal',
                'needed_by' => now()->addDays(1),
            ],
            [
                'organization' => $hospital2,
                'patient_name' => 'Patient ' . rand(400, 499),
                'blood_group' => 'AB-',
                'genotype' => 'AA',
                'units_needed' => 4,
                'type' => 'Blood',
                'request_source' => 'blood_banks',
                'urgency_level' => 'High',
                'needed_by' => now()->addHours(18),
            ],
            [
                'organization' => $hospital1,
                'patient_name' => 'Patient ' . rand(500, 599),
                'blood_group' => 'O-',
                'genotype' => 'AS',
                'units_needed' => 6,
                'type' => 'Blood',
                'request_source' => 'both',
                'urgency_level' => 'Critical',
                'needed_by' => now()->addHours(4),
            ],
        ];

        foreach ($requestConfigs as $config) {
            $org = $config['organization'];
            unset($config['organization']);

            $request = BloodRequest::create(array_merge($config, [
                'organization_id' => $org->id,
                'status' => 'Pending',
                'notes' => 'Sample request for patient needing units.',
            ]));

            // Distribute based on request_source
            if ($config['request_source'] === 'blood_banks' || $config['request_source'] === 'both') {
                $bloodBanks = Organization::where('role', 'blood_banks')
                    ->where('status', 'Active')
                    ->where('id', '!=', $org->id)
                    ->get();

                foreach ($bloodBanks as $bb) {
                    OrganizationRequest::create([
                        'blood_request_id' => $request->id,
                        'organization_id' => $bb->id,
                        'request_source' => $config['request_source'],
                        'status' => 'Pending',
                        'is_read' => false,
                    ]);
                }
            }

            if ($config['request_source'] === 'donors' || $config['request_source'] === 'both') {
                $donors = User::where('role', 'donor')->get();

                foreach ($donors as $donor) {
                    UserRequest::create([
                        'blood_request_id' => $request->id,
                        'user_id' => $donor->id,
                        'request_source' => $config['request_source'],
                        'is_read' => false,
                    ]);
                }
            }
        }

        $this->command->info('✅ FacilitiesSeeder completed with proper user structure!');
    }
}
