<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\User;
use App\Models\Donor;
use App\Models\InventoryItem;
use App\Models\BloodRequest;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StateSeeder::class,
            AdminSeeder::class,
            FacilitiesSeeder::class,
            BloodBankSeeder::class,
            DonorBadgeSeeder::class,
            DetailedSeeder::class,
        ]);

        $this->command->info('Database seeded successfully with all roles!');
    }
}
