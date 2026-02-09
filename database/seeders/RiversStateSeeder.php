<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Hash;
use App\Models\State;

class RiversStateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌊 Seeding Rivers State Data...');

        // 1. Ensure Rivers State Exists
        $riversState = State::firstOrCreate(
            ['state_code' => 'RI'],
            ['name' => 'Rivers', 'region' => 'South South']
        );

        // 2. Rivers State Ministry of Health (Regulatory Body)
        // User role: regulatory_body (singular)
        $mohUser = User::updateOrCreate(
            ['email' => 'admin@rsmh.gov.ng'],
            [
                'first_name' => 'Rivers State',
                'last_name' => 'Ministry of Health',
                'password' => Hash::make('password'),
                'phone' => '08030001000',
                'role' => 'regulatory_body',
                'gender' => 'Male',
                'email_verified_at' => now(),
            ]
        );

        Organization::updateOrCreate(
            ['license_number' => 'RSMH-001'],
            [
                'user_id' => $mohUser->id,
                'name' => 'Rivers State Ministry of Health',
                'type' => 'Regulatory Body',
                'role' => 'facilities', // Using facilities as a catch-all or regulatory_body if supported
                'contact_email' => 'contact@rsmh.gov.ng',
                'email' => 'admin@rsmh.gov.ng',
                'password' => Hash::make('password'),
                'phone' => '08030001000',
                'address' => 'State Secretariat Complex, Port Harcourt',
                'status' => 'Active',
                'latitude' => 4.7725,
                'longitude' => 7.0069,
                'description' => 'Overseeing all health facilities in Rivers State.',
                'services' => json_encode(['Regulation', 'Policy', 'Oversight']),
                'state_id' => $riversState->id,
            ]
        );

        // 3. Teaching Hospitals
        $hospitals = [
            [
                'name' => 'University of Port Harcourt Teaching Hospital',
                'email' => 'admin@upth.edu.ng',
                'license' => 'RS-HOSP-001',
                'address' => 'East-West Road, Port Harcourt',
                'lat' => 4.8967,
                'lng' => 6.9248,
                'phone' => '08031112222',
            ],
            [
                'name' => 'Rivers State University Teaching Hospital',
                'email' => 'admin@rsuth.gov.ng',
                'license' => 'RS-HOSP-002',
                'address' => 'Harley Street, Old GRA, Port Harcourt',
                'lat' => 4.7836,
                'lng' => 7.0094,
                'phone' => '08032223333',
            ],
            [
                'name' => 'Princess Medical Centre',
                'email' => 'admin@princessmedical.com',
                'license' => 'RS-HOSP-003',
                'address' => 'Trans Amadi Industrial Layout, Port Harcourt',
                'lat' => 4.8056,
                'lng' => 7.0378,
                'phone' => '08034445555',
            ]
        ];

        foreach ($hospitals as $hosp) {
            $user = User::updateOrCreate(
                ['email' => $hosp['email']],
                [
                    'first_name' => explode(' ', $hosp['name'])[0],
                    'last_name' => 'Hospital',
                    'password' => Hash::make('password'),
                    'phone' => $hosp['phone'],
                    'role' => 'facilities',
                    'gender' => 'Male',
                    'email_verified_at' => now(),
                ]
            );

            Organization::updateOrCreate(
                ['license_number' => $hosp['license']],
                [
                    'user_id' => $user->id,
                    'name' => $hosp['name'],
                    'type' => 'Hospital',
                    'role' => 'facilities',
                    'contact_email' => $hosp['email'],
                    'email' => $hosp['email'],
                    'password' => Hash::make('password'),
                    'phone' => $hosp['phone'],
                    'address' => $hosp['address'],
                    'status' => 'Active',
                    'latitude' => $hosp['lat'],
                    'longitude' => $hosp['lng'],
                    'description' => 'Premier healthcare facility in Rivers State.',
                    'services' => json_encode(['Emergency', 'Surgery', 'Pediatrics', 'Maternity']),
                    'state_id' => $riversState->id,
                ]
            );
        }

        // 4. Blood Banks
        $bloodBanks = [
            [
                'name' => 'Port Harcourt Blood Bank',
                'email' => 'info@phbloodbank.com',
                'license' => 'RS-BB-001',
                'address' => 'Aba Road, Port Harcourt',
                'lat' => 4.8250,
                'lng' => 7.0250,
                'phone' => '08051112222',
            ],
            [
                'name' => 'Armed Forces Blood Center',
                'email' => 'admin@armedforcesbb.mil.ng',
                'license' => 'RS-BB-002',
                'address' => 'Heliconia Park, Port Harcourt',
                'lat' => 4.8400,
                'lng' => 7.0100,
                'phone' => '08052223333',
            ]
        ];

        foreach ($bloodBanks as $bb) {
            $user = User::updateOrCreate(
                ['email' => $bb['email']],
                [
                    'first_name' => explode(' ', $bb['name'])[0],
                    'last_name' => 'Blood Bank',
                    'password' => Hash::make('password'),
                    'phone' => $bb['phone'],
                    'role' => 'blood_banks',
                    'gender' => 'Male',
                    'email_verified_at' => now(),
                ]
            );

            $org = Organization::updateOrCreate(
                ['license_number' => $bb['license']],
                [
                    'user_id' => $user->id,
                    'name' => $bb['name'],
                    'type' => 'Blood Bank',
                    'role' => 'blood_banks',
                    'contact_email' => $bb['email'],
                    'email' => $bb['email'],
                    'password' => Hash::make('password'),
                    'phone' => $bb['phone'],
                    'address' => $bb['address'],
                    'status' => 'Active',
                    'latitude' => $bb['lat'],
                    'longitude' => $bb['lng'],
                    'description' => 'Standard blood banking facility.',
                    'services' => json_encode(['Blood Donation', 'Screening', 'Storage']),
                    'state_id' => $riversState->id,
                ]
            );

            // Seed some inventory for them
            $this->seedInventory($org);
        }

        $this->command->info('✅ Rivers State data seeded successfully!');
    }

    private function seedInventory($organization)
    {
        $bloodGroups = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
        foreach ($bloodGroups as $group) {
            InventoryItem::updateOrCreate(
                ['organization_id' => $organization->id, 'blood_group' => $group, 'type' => 'Whole Blood'],
                [
                    'units_in_stock' => rand(5, 100),
                    'threshold' => 10,
                    'expiry_date' => now()->addDays(rand(10, 45)),
                ]
            );
        }
    }
}
