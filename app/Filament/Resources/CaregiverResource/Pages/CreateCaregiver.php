<?php

namespace App\Filament\Resources\CaregiverResource\Pages;

use App\Filament\Resources\CaregiverResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateCaregiver extends CreateRecord
{
    protected static string $resource = CaregiverResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'Caregiver',
        ]);

        $data['user_id'] = $user->id;

        unset($data['name']);
        unset($data['email']);
        unset($data['user']);

        return $data;
    }
}
