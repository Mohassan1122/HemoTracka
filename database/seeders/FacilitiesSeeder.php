<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\BloodRequest;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FacilitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a Regulatory Body (Hospital) Organization
        $hospital = Organization::updateOrCreate(
            ['license_number' => 'HOSP-2025-001'],
            [
                'name' => 'Kuje National Hospital',
                'type' => 'Hospital',
                'contact_email' => 'contact@kujehospital.gov.ng',
                'phone' => '08012345678',
                'address' => '123 Hospital Road, Kuje, Abuja',
                'status' => 'Active',
                'latitude' => 8.8875,
                'longitude' => 7.2285,
            ]
        );

        // 2. Create a User for this Facility
        $user = User::updateOrCreate(
            ['email' => 'admin@kujehospital.gov.ng'],
            [
                'organization_id' => $hospital->id,
                'first_name' => 'Kuje',
                'last_name' => 'Hospital Admin',
                'phone' => '08087654321',
                'password' => Hash::make('password123'),
                'role' => 'facilities',
                'email_verified_at' => now(),
            ]
        );

        // 3. Create a Blood Bank to request from
        $bloodBank = Organization::updateOrCreate(
            ['license_number' => 'BB-2025-001'],
            [
                'name' => 'Central Blood Bank Abuja',
                'type' => 'Blood Bank',
                'contact_email' => 'info@centralbloodbank.org',
                'phone' => '08022223333',
                'address' => 'Garki District, Abuja',
                'status' => 'Active',
                'latitude' => 9.0765,
                'longitude' => 7.3985,
            ]
        );

        // 4. Seed some inventory for the blood bank
        $bloodGroups = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
        foreach ($bloodGroups as $group) {
            InventoryItem::updateOrCreate(
                [
                    'organization_id' => $bloodBank->id,
                    'blood_group' => $group,
                    'type' => 'Whole Blood',
                ],
                [
                    'units_in_stock' => rand(10, 50),
                    'threshold' => 5,
                    'expiry_date' => now()->addDays(rand(5, 30)),
                ]
            );
        }

        // 5. Create some sample blood requests from the hospital
        for ($i = 0; $i < 10; $i++) {
            BloodRequest::create([
                'organization_id' => $hospital->id,
                'blood_group' => $bloodGroups[array_rand($bloodGroups)],
                'units_needed' => rand(1, 10),
                'patient_name' => 'Patient ' . rand(100, 999),
                'hospital_unit' => ['ICU', 'Emergency', 'Ward A', 'Ward B', 'Surgery'][array_rand(['ICU', 'Emergency', 'Ward A', 'Ward B', 'Surgery'])],
                'type' => ['Emergent', 'Bulk', 'Routine'][array_rand(['Emergent', 'Bulk', 'Routine'])],
                'urgency_level' => ['Critical', 'High', 'Normal'][array_rand(['Critical', 'High', 'Normal'])],
                'status' => ['Pending', 'Approved', 'Completed', 'Cancelled'][array_rand(['Pending', 'Approved', 'Completed', 'Cancelled'])],
                'needed_by' => now()->addHours(rand(1, 48)),
                'notes' => 'Sample request for patient needing units.',
            ]);
        }
    }
}
