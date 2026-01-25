<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['name' => 'Lagos', 'state_code' => 'LA', 'region' => 'South West'],
            ['name' => 'Kano', 'state_code' => 'KN', 'region' => 'North West'],
            ['name' => 'Rivers', 'state_code' => 'RI', 'region' => 'South South'],
            ['name' => 'Abuja', 'state_code' => 'AB', 'region' => 'North Central'],
            ['name' => 'Oyo', 'state_code' => 'OY', 'region' => 'South West'],
        ];

        foreach ($states as $state) {
            DB::table('states')->updateOrInsert(
                ['state_code' => $state['state_code']],
                $state
            );
        }

        $this->command->info('✅ States seeded successfully!');
    }
}
