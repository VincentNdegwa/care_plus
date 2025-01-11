<?php

namespace App\Http\Controllers\SideEffect;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\SideEffect;
use Illuminate\Http\Request;

class FetchSideEffectsController extends Controller
{
    public function getMedicationSideEffects(Request $request)
    {

        try {
            $validated = $request->validate([
                'patient_id' => 'required|integer|exists:patients,id',
                'medication_id' => 'nullable|integer|exists:medications,id',
                'severity' => 'nullable|in:mild,moderate,severe',
                'from_datetime' => 'nullable|date|before_or_equal:to_datetime',
                'to_datetime' => 'nullable|date|after_or_equal:from_datetime',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page_number' => 'nullable|integer|min:1',
            ]);

            $validated['per_page'] = $validated['per_page'] ?? 20;
            $validated['page_number'] = $validated['page_number'] ?? 1;

            SideEffect::with("");

            // Medication::where('patient_id', $validated['patient_id'])->with('sideEffects')

        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                "errors" => $th->errors(),
            ], 422);
        }
    }
}
