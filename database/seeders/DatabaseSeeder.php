<?php

namespace Database\Seeders;

use App\Models\Caregiver;
use App\Models\Doctor;
use App\Models\Medication\MedicationRoute;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserProfile;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => "Patient"
        ]);

        switch ($user->role) {
            case 'Doctor':
                Doctor::create([
                    'user_id' => $user->id,
                ]);
                break;
            case 'Caregiver':
                Caregiver::create([
                    'user_id' => $user->id,
                ]);
                break;
            case 'Patient':
                Patient::create([
                    'user_id' => $user->id,
                ]);
                break;
            default:
                break;
        }
        UserProfile::create([
            'user_id' => $user->id,
        ]);

        $this->call(MedicationFormsSeeder::class);
        $this->call(MedicationUnitsSeeder::class);
        $this->call(MedicationRoutesSeeder::class);
        $this->call(MedicationFrequenciesSeeder::class);
    }
}
