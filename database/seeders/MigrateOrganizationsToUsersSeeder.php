<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class MigrateOrganizationsToUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates User records for existing Organizations that don't have one.
     * This ensures backward compatibility during the auth migration.
     */
    public function run(): void
    {
        $organizations = Organization::whereNull('user_id')->get();

        $this->command->info("Found {$organizations->count()} organizations without user_id...");

        foreach ($organizations as $org) {
            // Check if a user with this email already exists
            $existingUser = User::where('email', $org->email)->first();

            if ($existingUser) {
                // Link existing user to organization
                $org->update(['user_id' => $existingUser->id]);
                $this->command->info("Linked existing user to: {$org->name}");
                continue;
            }

            // Create new User record
            $user = User::create([
                'email' => $org->email,
                'password' => $org->password, // Already hashed
                'first_name' => $org->name,
                'last_name' => '',
                'role' => $org->role ?? 'blood_banks',
                'phone' => $org->phone,
                'address' => $org->address,
            ]);

            // Link organization to user
            $org->update(['user_id' => $user->id]);

            $this->command->info("Created user for: {$org->name}");
        }

        $this->command->info('Migration complete!');
    }
}
