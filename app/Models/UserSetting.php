<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    private static $settingsTemplate = null;

    public static function getSettingsTemplate()
    {
        if (self::$settingsTemplate === null) {
            $path = base_path('settings.json');
            if (!File::exists($path)) {
                throw new \RuntimeException('Settings file not found');
            }
            self::$settingsTemplate = json_decode(File::get($path), true)['settings'];
        }
        return self::$settingsTemplate;
    }

    private function arrayMergeCustom($template, $userSettings)
    {
        $result = [];
        $userRole = strtolower($this->user->role);
        
        if (isset($template['all_users'])) {
            $result = $template['all_users'];
        }

        if (isset($template[$userRole])) {
            foreach ($template[$userRole] as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = $value;
                } else if (is_array($value) && is_array($result[$key])) {
                    $result[$key] = array_merge($result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        if (!empty($userSettings)) {
            foreach ($result as $key => $defaultValue) {
                if (isset($userSettings[$key])) {
                    if (is_array($defaultValue) && is_array($userSettings[$key])) {
                        $result[$key] = $this->mergeArraysRecursively($defaultValue, $userSettings[$key]);
                    } else {
                        $result[$key] = $userSettings[$key];
                    }
                }
            }
        }

        return $result;
    }

    private function mergeArraysRecursively($template, $userArray)
    {
        $result = $template;

        foreach ($template as $key => $value) {
            if (isset($userArray[$key])) {
                if (is_array($value) && is_array($userArray[$key])) {
                    $result[$key] = $this->mergeArraysRecursively($value, $userArray[$key]);
                } else {
                    $result[$key] = $userArray[$key];
                }
            }
        }

        return $result;
    }

    public function getMergedSettings()
    {
        $template = self::getSettingsTemplate();
        return $this->arrayMergeCustom($template, $this->settings);
    }

    public function getAvailableSettings()
    {
        $template = self::getSettingsTemplate();
        return $template;
    }

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

    public function updateSettings(array $newSettings)
    {
        $template = self::getSettingsTemplate();
        $this->settings = $this->arrayMergeCustom($template, $newSettings);
        return $this->save();
    }
} 