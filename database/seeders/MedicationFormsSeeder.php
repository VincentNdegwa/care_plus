<?php

namespace Database\Seeders;

use App\Models\Medication\MedicationForm;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicationFormsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $forms = [
            'tablet',    // Tablet form
            'pill',      // Pill form (similar to tablet)
            'liquid',    // Liquid form (e.g., syrups, solutions)
            'injection', // Injectable form (e.g., IV, intramuscular, subcutaneous)
            'capsule',   // Capsule form
            'ointment',  // Ointment or cream (topical application)
            'syringe',   // Syringe form (used for liquid in injectable or oral)
            'patch',     // Patch (transdermal)
            'suppository', // Suppository (rectal or vaginal)
            'inhaler',   // Inhaler (for respiratory conditions)
            'drop',      // Drop form (e.g., eye drops)
            'vial',      // Vial (used for injectable medication)
            'ampoule',   // Ampoule (sealed container for injectable meds)
        ];

        foreach ($forms as $form) {
            MedicationForm::updateOrCreate(['name' => $form]);
        }
    }
}
