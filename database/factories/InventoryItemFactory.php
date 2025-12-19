<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Organization;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'blood_group' => $this->faker->randomElement(['A+', 'B+', 'O+']),
            'type' => 'Whole Blood',
            'units_in_stock' => $this->faker->numberBetween(0, 50),
            'threshold' => 10,
            'location' => 'Fridge 1',
            'expiry_date' => now()->addDays(30),
        ];
    }
}
