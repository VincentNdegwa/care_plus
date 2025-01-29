<?php

namespace App\Http\Controllers\HealthVitals;

use App\Http\Controllers\Controller;
use App\Models\HealthVital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Validation\ValidationException;

class HealthVitalsController extends Controller
{
    protected function validateRequest(Request $request)
    {
        return $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'vital_data' => 'required|array',
            'vital_data.*.name' => 'required|string',
            'vital_data.*.value' => 'required|string',
        ]);
    }

    protected function saveHealthVital($validatedData)
    {


        $formatedData = [];
        foreach ($validatedData['vital_data'] as $value) {
            $formatedData[] = HealthVital::formatVitalData($value['name'], $value['value']);
        }

        $healthVital = HealthVital::updateOrCreate(
            ['patient_id' => $validatedData['patient_id']],
            ['vital_data' => $formatedData]
        );
        return HealthVital::getPatientVitalsAndCheckRange($validatedData['patient_id']);
    }

    public function create(Request $request)
    {
        try {
            $healthVital = $this->saveHealthVital(
                $this->validateRequest($request)
            );

            return $healthVital;
        } catch (ValidationException $e) {
            return $this->returnResponse(true, $e->getMessage(), [], $e->errors());
        } catch (Exception $e) {
            return $this->returnResponse(true, 'An error occurred while creating the health vital: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $healthVital = $this->saveHealthVital(
                $this->validateRequest($request)
            );

            return $healthVital;
        } catch (ModelNotFoundException $e) {
            return $this->returnResponse(true, 'Health vital not found.');
        } catch (ValidationException $e) {
            return $this->returnResponse(true, $e->getMessage(), [], $e->errors());
        } catch (Exception $e) {
            return $this->returnResponse(true, 'An error occurred while updating the health vital: ' . $e->getMessage());
        }
    }


    public function index($patient_id)
    {
        return HealthVital::getPatientVitalsAndCheckRange($patient_id);
    }

    private function returnResponse($error, $message, $data = [], $errors = [])
    {
        if ($error) {
            return response()->json([
                'error' => $error,
                'message' => $message,
                'errors' => $errors,
            ]);
        } else {
            return response()->json([
                'error' => $error,
                'message' => $message,
                'data' => $data
            ]);
        }
    }
}
