<?php

namespace Database\Seeders;

use App\Models\DonorBadge;
use Illuminate\Database\Seeder;

class DonorBadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'First Timer',
                'slug' => 'first-timer',
                'description' => 'Completed your first blood donation. Welcome to the lifesaving community!',
                'icon' => '🎉',
                'color' => '#10B981',
                'criteria_type' => 'first_donation',
                'criteria_value' => 1,
                'points' => 50,
            ],
            [
                'name' => 'Regular Donor',
                'slug' => 'regular-donor',
                'description' => 'Donated blood 5 times. You are making a difference!',
                'icon' => '🩸',
                'color' => '#EF4444',
                'criteria_type' => 'donation_count',
                'criteria_value' => 5,
                'points' => 100,
            ],
            [
                'name' => 'Bronze Donor',
                'slug' => 'bronze-donor',
                'description' => 'Donated blood 10 times. Your commitment saves lives!',
                'icon' => '🥉',
                'color' => '#CD7F32',
                'criteria_type' => 'donation_count',
                'criteria_value' => 10,
                'points' => 200,
            ],
            [
                'name' => 'Silver Donor',
                'slug' => 'silver-donor',
                'description' => 'Donated blood 25 times. You are a true hero!',
                'icon' => '🥈',
                'color' => '#C0C0C0',
                'criteria_type' => 'donation_count',
                'criteria_value' => 25,
                'points' => 500,
            ],
            [
                'name' => 'Gold Donor',
                'slug' => 'gold-donor',
                'description' => 'Donated blood 50 times. You are legendary!',
                'icon' => '🥇',
                'color' => '#FFD700',
                'criteria_type' => 'donation_count',
                'criteria_value' => 50,
                'points' => 1000,
            ],
            [
                'name' => 'Rare Blood Hero',
                'slug' => 'rare-blood-hero',
                'description' => 'You have a rare blood type and are helping those in critical need!',
                'icon' => '💎',
                'color' => '#8B5CF6',
                'criteria_type' => 'blood_type_rare',
                'criteria_value' => 1,
                'points' => 150,
            ],
            [
                'name' => 'Gallon Club',
                'slug' => 'gallon-club',
                'description' => 'Donated a gallon (8 units) of blood. Incredible achievement!',
                'icon' => '🏆',
                'color' => '#F59E0B',
                'criteria_type' => 'units_donated',
                'criteria_value' => 8,
                'points' => 250,
            ],
            [
                'name' => 'Two Gallon Club',
                'slug' => 'two-gallon-club',
                'description' => 'Donated two gallons (16 units) of blood. Outstanding!',
                'icon' => '🌟',
                'color' => '#3B82F6',
                'criteria_type' => 'units_donated',
                'criteria_value' => 16,
                'points' => 500,
            ],
        ];

        foreach ($badges as $badge) {
            DonorBadge::updateOrCreate(
                ['slug' => $badge['slug']],
                $badge
            );
        }
    }
}
