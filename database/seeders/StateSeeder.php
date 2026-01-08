<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['name' => 'Lagos', 'code' => 'LA', 'region' => 'South-West'],
            ['name' => 'Oyo', 'code' => 'OY', 'region' => 'South-West'],
            ['name' => 'Ogun', 'code' => 'OG', 'region' => 'South-West'],
            ['name' => 'Osun', 'code' => 'OS', 'region' => 'South-West'],
            ['name' => 'Ondo', 'code' => 'OD', 'region' => 'South-West'],
            ['name' => 'Ekiti', 'code' => 'EK', 'region' => 'South-West'],
            
            ['name' => 'Rivers', 'code' => 'RV', 'region' => 'South-South'],
            ['name' => 'Bayelsa', 'code' => 'BY', 'region' => 'South-South'],
            ['name' => 'Delta', 'code' => 'DT', 'region' => 'South-South'],
            ['name' => 'Akwa Ibom', 'code' => 'AK', 'region' => 'South-South'],
            ['name' => 'Cross River', 'code' => 'CR', 'region' => 'South-South'],
            
            ['name' => 'Abia', 'code' => 'AB', 'region' => 'South-East'],
            ['name' => 'Anambra', 'code' => 'AN', 'region' => 'South-East'],
            ['name' => 'Enugu', 'code' => 'EN', 'region' => 'South-East'],
            ['name' => 'Ebonyi', 'code' => 'EB', 'region' => 'South-East'],
            ['name' => 'Imo', 'code' => 'IM', 'region' => 'South-East'],
            
            ['name' => 'Kaduna', 'code' => 'KD', 'region' => 'North-Central'],
            ['name' => 'Kogi', 'code' => 'KG', 'region' => 'North-Central'],
            ['name' => 'Niger', 'code' => 'NG', 'region' => 'North-Central'],
            ['name' => 'Plateau', 'code' => 'PL', 'region' => 'North-Central'],
            ['name' => 'Nasarawa', 'code' => 'NS', 'region' => 'North-Central'],
            
            ['name' => 'Kano', 'code' => 'KN', 'region' => 'North-West'],
            ['name' => 'Katsina', 'code' => 'KT', 'region' => 'North-West'],
            ['name' => 'Kebbi', 'code' => 'KB', 'region' => 'North-West'],
            ['name' => 'Sokoto', 'code' => 'SK', 'region' => 'North-West'],
            ['name' => 'Zamfara', 'code' => 'ZM', 'region' => 'North-West'],
            ['name' => 'Jigawa', 'code' => 'JG', 'region' => 'North-West'],
            
            ['name' => 'Katsina', 'code' => 'KT', 'region' => 'North-East'],
            ['name' => 'Adamawa', 'code' => 'AD', 'region' => 'North-East'],
            ['name' => 'Borno', 'code' => 'BO', 'region' => 'North-East'],
            ['name' => 'Taraba', 'code' => 'TR', 'region' => 'North-East'],
            ['name' => 'Gombe', 'code' => 'GM', 'region' => 'North-East'],
            ['name' => 'Yobe', 'code' => 'YB', 'region' => 'North-East'],
            
            ['name' => 'Abuja', 'code' => 'FCT', 'region' => 'North-Central'],
        ];

        foreach ($states as $state) {
            State::firstOrCreate(['code' => $state['code']], $state);
        }
    }
}
