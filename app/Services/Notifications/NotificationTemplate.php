<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\File;

class NotificationTemplate
{
    protected static $templates = null;

    public static function load()
    {
        if (self::$templates === null) {
            $path = base_path('notification.json');
            if (!File::exists($path)) {
                throw new \RuntimeException('Notification templates file not found');
            }
            self::$templates = json_decode(File::get($path), true);
        }
        return self::$templates;
    }

    public static function get($event, $replacements = [])
    {
        $templates = self::load();
        $template = collect($templates)->firstWhere('event', $event);

        if (!$template) {
            throw new \RuntimeException("Notification template not found for event: {$event}");
        }

        $roomName = null;
        if ($template['receiver'] === 'room' && isset($template['room_pattern'])) {
            $roomName = self::replaceVariables($template['room_pattern'], $replacements);
        }

        return [
            'title' => self::replaceVariables($template['title'], $replacements),
            'body' => self::replaceVariables($template['description'], $replacements),
            'event' => $event,
            'receiver' => $template['receiver'],
            'room_name' => $roomName
        ];
    }

    protected static function replaceVariables($text, $replacements)
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace("[$key]", $value, $text);
        }
        return $text;
    }
} 