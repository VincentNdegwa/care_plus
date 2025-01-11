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

            $query = SideEffect::query();

            $query->with('medication')->where('patient_id', $validated['patient_id']);

            if (!empty($validated['medication_id'])) {
                $query->where('medication_id', $validated['medication_id']);
            }

            if (!empty($validated['severity'])) {
                $query->where('severity', $validated['severity']);
            }

            if (!empty($validated['from_datetime'])) {
                $query->where('datetime', '>=', $validated['from_datetime']);
            }

            if (!empty($validated['to_datetime'])) {
                $query->where('datetime', '<=', $validated['to_datetime']);
            }

            $sideEffects = $query->paginate(
                $validated['per_page'],
                ['*'],
                'page',
                $validated['page_number']
            );

            return $this->formatPagination($sideEffects);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                "errors" => $th->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function formatPagination($model)
    {
        return response()->json([
            'error' => false,
            'data' => $model->items(),
            'pagination' => [
                'current_page' => $model->currentPage(),
                'total_pages' => $model->lastPage(),
                'total_items' => $model->total(),
                'per_page' => $model->perPage(),
            ],
        ]);
    }
}
