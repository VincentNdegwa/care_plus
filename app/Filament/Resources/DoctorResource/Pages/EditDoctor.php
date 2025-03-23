<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDoctor extends EditRecord
{
    protected static string $resource = DoctorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $doctor = $this->record;
        $user = $doctor->user;

        $data['name'] = $user->name;
        $data['email'] = $user->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $doctor = $this->record;
        $user = $doctor->user;

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        unset($data['name']);
        unset($data['email']);

        return $data;
    }
}
