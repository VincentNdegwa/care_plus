<?php

namespace App\Services\Notifications;

use App\Services\FCM\FCMService;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $fcm;

    public function __construct()
    {
        $this->fcm = new FCMService();
    }

    public function send($event, $recipients, $replacements = [], $additionalData = [])
    {
        try {
            $notification = NotificationTemplate::get($event, $replacements);
            
            $data = array_merge([
                'type' => $event,
                'notification' => $notification
            ], $additionalData);

            Log::info('Sending notification', [
                "data" => $data,
            ]);

            if ($notification['receiver'] === 'room') {
                return $this->sendToRoom($notification, $data);
            }

            return $this->sendToRecipients($notification, $recipients, $data);

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function sendToRecipients($notification, $recipients, $data)
    {
        if (!is_array($recipients)) {
            $recipients = [$recipients];
        }

        $successCount = 0;
        foreach ($recipients as $recipient) {
            $success = match ($notification['receiver']) {
                'patient' => $this->fcm->sendToUser($recipient, $notification['title'], $notification['body'], $data),
                'doctor' => $this->fcm->sendToUser($recipient, $notification['title'], $notification['body'], $data),
                'caregiver' => $this->fcm->sendToUser($recipient, $notification['title'], $notification['body'], $data),
                default => false
            };

            if ($success) $successCount++;
        }

        return $successCount > 0;
    }

    protected function sendToRoom($notification, $data)
    {
        return $this->fcm->sendToRoom(
            $notification['room_name'],
            $notification['title'],
            $notification['body'],
            $data
        );
    }
} 