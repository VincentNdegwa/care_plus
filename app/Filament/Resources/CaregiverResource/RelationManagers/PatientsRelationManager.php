<?php

namespace App\Filament\Resources\CaregiverResource\RelationManagers;

use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientsRelationManager extends RelationManager
{
    protected static string $relationship = 'patients';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('Patient')
                    ->options(
                        Patient::whereHas('user', fn($q) => $q->where('role', 'Patient'))
                            ->with('user')
                            ->get()
                            ->mapWithKeys(fn($patient) => [$patient->id => $patient->user->name])
                    )
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('relation')
                    ->options([
                        "Caregiver" => "Caregiver",
                        "Family Relative" => "Family Relative",
                        "Friend" => "Friend",
                        "Neighbor" => "Neighbor",
                        "Other" => "Other",
                    ])
                    ->default('Caregiver')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('patient_Id')
            ->columns([
                Tables\Columns\TextColumn::make('patient.user.name')
                    ->label('Patient'),

                Tables\Columns\TextColumn::make('relation')
                    ->label('Relationship'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
