<?php

namespace Tests\Feature;

use App\Models\Medication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a medication.
     *
     * @return void
     */
    public function test_create_medication()
    {
        // Define the medication data
        $medicationData = [
            'name' => 'Aspirin',
            'dosage' => '500mg',
            'frequency' => 'Once a day',
            'patient_id' => 1, // Assuming a patient with ID 1 exists
        ];

        // Send a POST request to the medication creation endpoint
        $response = $this->postJson('/v1/medications', $medicationData);

        // Assert the response status is 201 (created)
        $response->assertStatus(201);

        // Assert the medication was created in the database
        $this->assertDatabaseHas('medications', [
            'name' => 'Aspirin',
            'dosage' => '500mg',
        ]);
    }
}
