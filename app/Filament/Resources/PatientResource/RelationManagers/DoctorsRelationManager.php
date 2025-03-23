<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DoctorsRelationManager extends RelationManager
{
    protected static string $relationship = 'doctors';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('doctor_id')
                    ->options(
                        \App\Models\Doctor::all()->mapWithKeys(function ($doctor) {
                            return [$doctor->id => $doctor->user->name];
                        })
                    )
                    ->label('Doctor')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('isMain')
                    ->label('Is Main')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('doctor_id')
            ->columns([
                Tables\Columns\TextColumn::make('doctor.user.name')
                    ->label('Doctor'),
                Tables\Columns\TextColumn::make('doctor.specialization')
                    ->label('Specialization'),
                Tables\Columns\IconColumn::make('isMain')
                    ->label('Is Main')
                    ->boolean(),
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
