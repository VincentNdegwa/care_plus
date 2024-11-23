<?php

namespace Database\Seeders;

use App\Models\Medication\MedicationRoute;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicationRoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = [
            ['name' => 'Oral (PO)', 'description' => 'The patient swallows a tablet or capsule.'],
            ['name' => 'Sublingual (SL)', 'description' => 'Applied under the tongue.'],
            ['name' => 'Enteral (NG or PEG)', 'description' => 'Administered via a tube directly into the GI tract.'],
            ['name' => 'Rectal (PR)', 'description' => 'Administered via rectal suppository.'],
            ['name' => 'Inhalation (INH)', 'description' => 'The patient breathes in medication from an inhaler.'],
            ['name' => 'Intramuscular (IM)', 'description' => 'Administered via an injection into a muscle.'],
            ['name' => 'Subcutaneous', 'description' => 'Administered via injection into the fat tissue beneath the skin.'],
            ['name' => 'Transdermal (TD)', 'description' => 'Administered by applying a patch on the skin.'],
        ];

        foreach ($routes as $route) {
            MedicationRoute::updateOrCreate($route);
        }
    }
}
