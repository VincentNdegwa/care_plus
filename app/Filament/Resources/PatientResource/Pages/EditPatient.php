<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $patient = $this->record;
        $user = $patient->user;

        $data['name'] = $user->name;
        $data['email'] = $user->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $patient = $this->record;
        $user = $patient->user;

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        unset($data['name']);
        unset($data['email']);

        return $data;
    }
}
