<?php

namespace App\Http\Controllers\SideEffect;

use App\Http\Controllers\Controller;
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
                'errors' => $th->errors()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
