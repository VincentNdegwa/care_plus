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
                Forms\Components\Section::make('User Management')
                    ->schema([
                        Forms\Components\Select::make('settings.user_management.timezone')
                            ->label('Timezone')
                            ->options([
                                'UTC' => 'UTC',
                                'Africa/Nairobi' => 'Africa/Nairobi',
                            ])
                            ->default('UTC'),
                        Forms\Components\Select::make('settings.user_management.language_preferences')
                            ->label('Language')
                            ->options([
                                'en' => 'English',
                                'sw' => 'Swahili',
                            ])
                            ->default('en'),
                        Forms\Components\Section::make('Notification Preferences')
                            ->schema([
                                Forms\Components\Toggle::make('settings.user_management.notification_preferences.sms')
                                    ->label('SMS')
                                    ->default(true),
                                Forms\Components\Toggle::make('settings.user_management.notification_preferences.email')
                                    ->label('Email')
                                    ->default(true),
                                Forms\Components\Toggle::make('settings.user_management.notification_preferences.push_notifications')
                                    ->label('Push Notifications')
                                    ->default(true),
                            ]),
                    ]),
                Forms\Components\Section::make('Emergency Alerts')
                    ->schema([
                        Forms\Components\Section::make('Alert Preferences')
                            ->schema([
                                Forms\Components\Toggle::make('settings.emergency_alerts.alert_preferences.sms')
                                    ->label('SMS')
                                    ->default(false),
                                Forms\Components\Toggle::make('settings.emergency_alerts.alert_preferences.email')
                                    ->label('Email')
                                    ->default(false),
                            ]),
                    ]),
                Forms\Components\Section::make('Reporting Analytics')
                    ->schema([
                        Forms\Components\Section::make('Report Preferences')
                            ->schema([
                                Forms\Components\Toggle::make('settings.reporting_analytics.generate_reports.health_vitals')
                                    ->label('Health Vitals')
                                    ->default(true),
                                Forms\Components\Toggle::make('settings.reporting_analytics.generate_reports.diagnosis_history')
                                    ->label('Diagnosis History')
                                    ->default(true),
                                Forms\Components\Toggle::make('settings.reporting_analytics.generate_reports.medication_adherence')
                                    ->label('Medication Adherence')
                                    ->default(true),
                            ]),
                        Forms\Components\Toggle::make('settings.reporting_analytics.receive_health_statement')
                            ->label('Receive Health Statement')
                            ->default(true),
                    ]),
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
