<?php

namespace Database\Seeders;

use App\Models\Medication\MedicationUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicationUnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Mass-based units
            'mg',  // milligrams
            'g',   // grams
            'mcg', // micrograms
            'kg',  // kilograms (rare for medication but possible in some cases)

            // Volume-based units
            'ml',  // milliliters
            'l',   // liters
            'dl',  // deciliters (less common but can be used in some formulations)

            // International Units
            'iu',  // International units

            // Dosage forms / Syringes
            'unit', // Unit dose (can be used for things like pills or ampoules)
            'syringe', // Syringe (this would represent a dosage in terms of syringes)
            'drops', // Drops (for liquid forms like eye drops, etc.)
        ];

        foreach ($units as $unit) {
            MedicationUnit::updateOrCreate(['name' => $unit]);
        }
    }
}
