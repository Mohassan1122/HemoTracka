<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\State;
use App\Models\Organization;
use App\Models\RegulatoryBody;
use App\Models\ComplianceRequest;
use App\Models\ComplianceMonitoring;
use App\Models\RegulatoryBodySocialConnection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RegulatoryBodySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some states for regulatory bodies
        $lagosState = State::where('code', 'LA')->first();
        $abujaState = State::where('code', 'NG')->first(); // Niger for North-Central
        $kanosState = State::where('code', 'KN')->first();

        if (!$lagosState || !$abujaState || !$kanosState) {
            $this->command->warn('States not found. Please run StateSeeder first.');
            return;
        }

        // 1. Create Regulatory Body Users
        $regulatoryBodies = [
            [
                'email' => 'fda-nigeria@gov.ng',
                'first_name' => 'FDA',
                'last_name' => 'Nigeria',
                'phone' => '09012345001',
                'role' => 'regulatory_body',
                'institution_name' => 'Federal Drug Administration - Nigeria',
                'license_number' => 'FDA-NG-2025-001',
                'level' => 'federal',
                'state' => $lagosState,
                'address' => 'Oshodi, Lagos',
                'phone_number' => '09012345001',
                'company_website' => 'https://www.fda.gov.ng',
            ],
            [
                'email' => 'nbs-abuja@gov.ng',
                'first_name' => 'NBS',
                'last_name' => 'Blood Services',
                'phone' => '09012345002',
                'role' => 'regulatory_body',
                'institution_name' => 'National Blood Transfusion Service - Abuja',
                'license_number' => 'NBTS-ABJ-2025-001',
                'level' => 'federal',
                'state' => $abujaState,
                'address' => 'Asokoro, Abuja',
                'phone_number' => '09012345002',
                'company_website' => 'https://www.nbts.gov.ng',
            ],
            [
                'email' => 'kano-health-board@gov.ng',
                'first_name' => 'Kano',
                'last_name' => 'Health Board',
                'phone' => '09012345003',
                'role' => 'regulatory_body',
                'institution_name' => 'Kano State Health Services Board',
                'license_number' => 'KSHSB-2025-001',
                'level' => 'state',
                'state' => $kanosState,
                'address' => 'Kofar Mata, Kano',
                'phone_number' => '09012345003',
                'company_website' => 'https://www.kano-health.gov.ng',
            ],
        ];

        foreach ($regulatoryBodies as $rbData) {
            // Create or update User with regulatory_body role
            $user = User::updateOrCreate(
                ['email' => $rbData['email']],
                [
                    'first_name' => $rbData['first_name'],
                    'last_name' => $rbData['last_name'],
                    'phone' => $rbData['phone'],
                    'password' => Hash::make('password'),
                    'role' => $rbData['role'],
                    'email_verified_at' => now(),
                ]
            );

            // Create associated RegulatoryBody record
            $regulatoryBody = RegulatoryBody::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'institution_name' => $rbData['institution_name'],
                    'license_number' => $rbData['license_number'],
                    'level' => $rbData['level'],
                    'state_id' => $rbData['state']->id,
                    'email' => $rbData['email'],
                    'phone_number' => $rbData['phone_number'],
                    'address' => $rbData['address'],
                    'company_website' => $rbData['company_website'],
                    'work_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'work_hours' => ['09:00', '17:00'],
                    'notification_preferences' => [
                        'email_notifications' => true,
                        'sms_notifications' => true,
                        'in_app_notifications' => true,
                    ],
                    'is_active' => true,
                ]
            );

            // 2. Add Social Connections for each regulatory body
            $socialConnections = [
                [
                    'platform' => 'twitter',
                    'handle' => strtolower(str_replace(' ', '_', $rbData['first_name'])) . '_official',
                    'url' => 'https://twitter.com/' . strtolower(str_replace(' ', '_', $rbData['first_name'])),
                ],
                [
                    'platform' => 'facebook',
                    'handle' => str_replace(' ', '', $rbData['institution_name']),
                    'url' => 'https://facebook.com/' . str_replace(' ', '', $rbData['institution_name']),
                ],
                [
                    'platform' => 'linkedin',
                    'handle' => str_replace(' ', '-', strtolower($rbData['institution_name'])),
                    'url' => 'https://linkedin.com/company/' . str_replace(' ', '-', strtolower($rbData['institution_name'])),
                ],
            ];

            foreach ($socialConnections as $connection) {
                RegulatoryBodySocialConnection::updateOrCreate(
                    [
                        'regulatory_body_id' => $regulatoryBody->id,
                        'platform' => $connection['platform'],
                    ],
                    [
                        'handle' => $connection['handle'],
                        'url' => $connection['url'],
                    ]
                );
            }

            // 3. Create Compliance Monitoring Records for each regulatory body
            $bloodBanks = Organization::where('type', 'Blood Bank')->get();
            $randomOrg = $bloodBanks->random();

            ComplianceMonitoring::updateOrCreate(
                [
                    'regulatory_body_id' => $regulatoryBody->id,
                    'inspection_id' => 'INS-' . $regulatoryBody->license_number . '-001',
                ],
                [
                    'organization_id' => $randomOrg->id,
                    'facility_type' => 'Blood Bank',
                    'compliance_status' => ['Compliant', 'Non-Compliant', 'Partially Compliant'][array_rand(['Compliant', 'Non-Compliant', 'Partially Compliant'])],
                    'last_inspection_date' => now()->subMonths(rand(1, 6)),
                    'next_inspection_date' => now()->addMonths(rand(1, 6)),
                    'violations_found' => rand(0, 5),
                    'notes' => 'Sample compliance monitoring record for ' . $rbData['institution_name'],
                ]
            );

            // 4. Create Compliance Requests (requests from blood banks to regulatory bodies)
            for ($i = 0; $i < 3; $i++) {
                ComplianceRequest::create([
                    'regulatory_body_id' => $regulatoryBody->id,
                    'organization_id' => $randomOrg->id,
                    'organization_type' => 'blood_bank',
                    'request_type' => ['License Renewal', 'Inspection Request', 'Compliance Verification'][array_rand(['License Renewal', 'Inspection Request', 'Compliance Verification'])],
                    'description' => 'Compliance request #' . ($i + 1) . ' for ' . $rbData['institution_name'],
                    'priority' => ['Low', 'Medium', 'High', 'Urgent'][array_rand(['Low', 'Medium', 'High', 'Urgent'])],
                    'status' => ['Pending', 'In Review', 'Approved', 'Rejected'][array_rand(['Pending', 'In Review', 'Approved', 'Rejected'])],
                    'submission_date' => now()->subDays(rand(1, 30)),
                    'required_documents' => [
                        'License Certificate',
                        'Quality Assurance Report',
                        'Safety Compliance Form',
                        'Staff Training Records',
                    ],
                    'notes' => 'Sample compliance request for tracking and documentation.',
                ]);
            }
        }

        $this->command->info('RegulatoryBodySeeder completed successfully!');
        $this->command->info('Created 3 regulatory body users with their compliance records and social connections.');
    }
}
