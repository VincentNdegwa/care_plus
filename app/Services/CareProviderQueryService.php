<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class CareProviderQueryService
{
    public function buildQuery(Builder $query, array $filters): Builder
    {
        $this->applyUserIdsFilter($query, $filters['user_ids'] ?? null);
        $this->applyRoleFilter($query, $filters['role'] ?? null);
        $this->applySearchFilter($query, $filters['search'] ?? null);
        $this->applyAgencyNameFilter($query, $filters['agency_name'] ?? null, $filters['role'] ?? null);
        $this->applyGenderFilter($query, $filters['gender'] ?? null);
        $this->applySpecializationFilter($query, $filters['specialization'] ?? null, $filters['role'] ?? null);
        $this->loadRelationships($query, $filters['role'] ?? null);
        
        return $query->where('role', '!=', 'patient');
    }

    private function applyUserIdsFilter(Builder $query, ?array $userIds): void
    {
        if (!empty($userIds)) {
            $query->whereIn('id', $userIds);
        }
    }

    private function applyRoleFilter(Builder $query, ?string $role): void
    {
        if ($role) {
            $query->where('role', $role);
        }
    }

    private function applySearchFilter(Builder $query, ?string $search): void
    {
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
    }

    private function applyAgencyNameFilter(Builder $query, ?string $agencyName, ?string $role): void
    {
        if ($agencyName && $role) {
            $relation = strtolower($role);
            $query->whereHas($relation, function ($query) use ($agencyName) {
                $query->where('agency_name', 'like', "%{$agencyName}%");
            });
        }
    }

    private function applyGenderFilter(Builder $query, ?string $gender): void
    {
        if ($gender) {
            $query->whereHas('profile', function ($query) use ($gender) {
                $query->where('gender', $gender);
            });
        }
    }

    private function applySpecializationFilter(Builder $query, ?string $specialization, ?string $role): void
    {
        if ($specialization && $role) {
            $relation = strtolower($role);
            $query->whereHas($relation, function ($query) use ($specialization) {
                $query->where('specialization', $specialization);
            });
        }
    }

    private function loadRelationships(Builder $query, ?string $role): void
    {
        $query->with(['profile']);
        
        if ($role) {
            $relation = strtolower($role);
            $query->with($relation);
        }
    }
} 