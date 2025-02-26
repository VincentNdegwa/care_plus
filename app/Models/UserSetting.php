<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods to get specific settings
    public function getNotificationPreferences()
    {
        return $this->settings['user_management']['notification_preferences'] ?? null;
    }

    public function getLanguagePreference()
    {
        return $this->settings['user_management']['language_preferences'] ?? 'en';
    }

    public function getTimezone()
    {
        return $this->settings['user_management']['timezone'] ?? 'UTC';
    }

    public function getEmergencyContacts()
    {
        return $this->settings['emergency_alerts']['emergency_contacts'] ?? [];
    }

    public function getAlertPreferences()
    {
        return $this->settings['emergency_alerts']['alert_preferences'] ?? null;
    }

    public function getReportingPreferences()
    {
        return $this->settings['reporting_analytics']['generate_reports'] ?? null;
    }

    public function getHealthStatementPreference()
    {
        return $this->settings['reporting_analytics']['receive_health_statement'] ?? true;
    }

    // Helper methods to update specific settings
    public function updateNotificationPreferences(array $preferences)
    {
        $settings = $this->settings;
        $settings['user_management']['notification_preferences'] = array_merge(
            $settings['user_management']['notification_preferences'] ?? [],
            $preferences
        );
        $this->settings = $settings;
        return $this->save();
    }

    public function updateLanguagePreference(string $language)
    {
        $settings = $this->settings;
        $settings['user_management']['language_preferences'] = $language;
        $this->settings = $settings;
        return $this->save();
    }

    public function updateTimezone(string $timezone)
    {
        $settings = $this->settings;
        $settings['user_management']['timezone'] = $timezone;
        $this->settings = $settings;
        return $this->save();
    }

    public function updateEmergencyContacts(array $contacts)
    {
        $settings = $this->settings;
        $settings['emergency_alerts']['emergency_contacts'] = $contacts;
        $this->settings = $settings;
        return $this->save();
    }

    public function updateAlertPreferences(array $preferences)
    {
        $settings = $this->settings;
        $settings['emergency_alerts']['alert_preferences'] = array_merge(
            $settings['emergency_alerts']['alert_preferences'] ?? [],
            $preferences
        );
        $this->settings = $settings;
        return $this->save();
    }

    public function updateReportingPreferences(array $preferences)
    {
        $settings = $this->settings;
        $settings['reporting_analytics']['generate_reports'] = array_merge(
            $settings['reporting_analytics']['generate_reports'] ?? [],
            $preferences
        );
        $this->settings = $settings;
        return $this->save();
    }

    public function updateHealthStatementPreference(bool $receive)
    {
        $settings = $this->settings;
        $settings['reporting_analytics']['receive_health_statement'] = $receive;
        $this->settings = $settings;
        return $this->save();
    }
} 