<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CaregiversRelationManager extends RelationManager
{
    protected static string $relationship = 'caregivers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('caregiver_id')
                    ->options(
                        \App\Models\Caregiver::all()->mapWithKeys(function ($caregiver) {
                            return [$caregiver->id => $caregiver->user->name];
                        })
                    )
                    ->label('Caregiver')
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
            ->recordTitleAttribute('caregiver_id')
            ->columns([
                Tables\Columns\TextColumn::make('caregiver.user.name')
                    ->label('Caregiver'),
                Tables\Columns\TextColumn::make('relation')
                    ->label('Relation'),
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
