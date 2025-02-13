<?php

namespace App\Http\Controllers\SideEffect;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\SideEffect;
use Exception;
use Illuminate\Http\Request;

class CreateSideEffectsController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'medication_id' => 'required|exists:medications,id',
                'datetime' => 'required|date',
                'side_effect' => 'required|string',
                'severity' => 'required|in:mild,moderate,severe',
                'duration' => 'nullable|integer',
                'notes' => 'nullable|string',
            ]);
            $medication = Medication::find($validatedData['medication_id']);
            if (!$medication) {
                return response()->json([
                    'error' => true,
                    'message' => 'Medication not found'
                ], 404);
            }

            $validatedData['patient_id'] = $medication->patient_id;

            $side_effect = SideEffect::create($validatedData);
            return response()->json([
                'error' => false,
                'message' => 'Side Effect created successfully',
                'side_effect' => $side_effect
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                'error' => true,
                "message" => $th->getMessage(),
            ],422);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ],500);
        }
    }
}
