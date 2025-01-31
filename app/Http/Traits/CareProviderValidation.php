<?php

namespace App\Http\Traits;

trait CareProviderValidation
{
    protected function getValidationRules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string',
            'role' => 'nullable|string|in:Doctor,Caregiver',
            'gender' => 'nullable|string|in:male,female,other',
            'agency_name' => 'nullable|string',
            'specialization' => 'nullable|string',
        ];
    }
} 