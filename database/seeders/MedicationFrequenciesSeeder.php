<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicationFrequenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $frequencies = [
            "Once a day",
            "Twice a day",
            "Three times a day",
            "Four times a day",
            "Once every other day",
            "Every third day",
            "Once a week",
            "Twice a week",
            "Three times a week",
            "Once a month",
            "Every 6 hours",
            "Every 8 hours",
            "Every 12 hours",
            "Every 24 hours",
            "Every hour",
            "Every 2 hours",
            "Every 4 hours",
            "Every 6 hours",
            "Every 30 minutes",
            "Every 45 minutes",
            "On demand",
            "As needed",
            "Until finished",
            "Until bottle is empty"
        ];

        foreach ($frequencies as $frequency) {
            DB::table('medication_frequencies')->insert([
                'frequency' => $frequency
            ]);
        }
    }
}
