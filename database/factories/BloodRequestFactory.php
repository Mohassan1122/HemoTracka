<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Organization;

class BloodRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'blood_group' => $this->faker->randomElement(['A+', 'B+', 'O+']),
            'units_needed' => $this->faker->numberBetween(1, 5),
            'urgency_level' => $this->faker->randomElement(['Critical', 'High', 'Normal']),
            'type' => $this->faker->randomElement(['Emergent', 'Bulk', 'Routine']),
            'status' => 'Pending',
            'patient_name' => $this->faker->name(),
            'hospital_unit' => 'ICU',
            'needed_by' => now()->addDays(2),
        ];
    }
}
