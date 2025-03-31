<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingsRelationManager extends RelationManager
{
    protected static string $relationship = 'settings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('settings.user_management.timezone')
                    ->label('Timezone')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->getTimezone();
                    }),
                Tables\Columns\TextColumn::make('settings.user_management.language_preferences')
                    ->label('Language')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->getLanguagePreference();
                    }),
                Tables\Columns\TextColumn::make('settings.user_management.notification_preferences')
                    ->label('Notifications')
                    ->formatStateUsing(function ($state, $record) {
                        $prefs = $record->getNotificationPreferences();
                        if (!$prefs) return '-';
                        return collect($prefs)
                            ->map(fn ($value, $key) => ucfirst($key) . ': ' . ($value ? 'Yes' : 'No'))
                            ->implode(', ');
                    }),
                Tables\Columns\TextColumn::make('settings.emergency_alerts.alert_preferences')
                    ->label('Alert Preferences')
                    ->formatStateUsing(function ($state, $record) {
                        $prefs = $record->getAlertPreferences();
                        if (!$prefs) return '-';
                        return collect($prefs)
                            ->map(fn ($value, $key) => ucfirst($key) . ': ' . ($value ? 'Yes' : 'No'))
                            ->implode(', ');
                    })
            ])
            ->filters([])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
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
