<?php

namespace App\Filament\Resources\CaregiverResource\Pages;

use App\Filament\Resources\CaregiverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCaregiver extends EditRecord
{
    protected static string $resource = CaregiverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $caregiver = $this->record;
        $user = $caregiver->user;

        $data['name'] = $user->name;
        $data['email'] = $user->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $caregiver = $this->record;
        $user = $caregiver->user;

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        unset($data['name']);
        unset($data['email']);

        return $data;
    }
}
