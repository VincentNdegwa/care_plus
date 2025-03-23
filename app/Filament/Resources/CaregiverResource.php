<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaregiverResource\Pages;
use App\Filament\Resources\CaregiverResource\RelationManagers\PatientsRelationManager;
use App\Models\Caregiver;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CaregiverResource extends Resource
{
    protected static ?string $model = Caregiver::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 4;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCaregivers::route('/'),
            'create' => Pages\CreateCaregiver::route('/create'),
            'edit' => Pages\EditCaregiver::route('/{record}/edit'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignorable: fn ($record) => $record?->user
                            ),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->visibleOn('create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Caregiver Details')
                    ->schema([
                        Forms\Components\TextInput::make('specialization')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('last_activity'),
                        Forms\Components\TextInput::make('agency_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('agency_contact')
                            ->label('Agency Contact')
                            ->maxLength(255)
                            ->tel() 
                            ->regex('/^\+?[1-9]\d{1,14}$/') 
                            ->placeholder('+254700123456')
                            ->helperText('Enter a valid phone number with country code')
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('specialization')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agency_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agency_contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_activity')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
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

    public static function getRelations(): array
    {
        return [
            PatientsRelationManager::class
        ];
    }
}
