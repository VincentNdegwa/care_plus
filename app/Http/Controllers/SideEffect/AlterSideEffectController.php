<?php

namespace App\Http\Controllers\SideEffect;

use App\Http\Controllers\Controller;
use App\Models\SideEffect;
use Exception;
use Illuminate\Http\Request;

class AlterSideEffectController extends Controller
{
    public function update($id, Request $request)
    {
        try {
            $validatedData = $request->validate([
                'datetime' => 'required|date',
                'side_effect' => 'required|string',
                'severity' => 'required|in:mild,moderate,severe',
                'duration' => 'nullable|integer',
                'notes' => 'nullable|string',
            ]);
            $side_effect = SideEffect::find($id);
            if ($side_effect) {
                $side_effect->update($validatedData);
                $side_effect->refresh();
                return response()->json([
                    'error' => false,
                    'message' => 'Side Effect Updated successfully',
                    'side_effect' => $side_effect
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'SideEffect not found'
                ], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                'error' => true,
                "message" => $th->getMessage(),
                'errors' => $th->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        $side_effect = SideEffect::find($id);
        if ($side_effect) {
            $side_effect->delete();
            return response()->json([
                'error' => false,
                'message' => 'Side Effect Deleted'
            ]);
        }
        return response()->json([
            'error' => true,
            'message' => 'Side Effects Not Found'
        ]);
    }
}
