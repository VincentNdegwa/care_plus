<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Services\FCM\FCMService;
use App\Services\MoveSMS\MoveSMSService;
use App\Jobs\SendSMSJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationMail;

class NotificationService
{
    protected $fcm;
    protected $smsService;

    public function __construct(FCMService $fcm, MoveSMSService $smsService)
    {
        $this->fcm = $fcm;
        $this->smsService = $smsService;
    }

    public function send($event, $userIds, $replacements = [], $additionalData = [],$notifiable=null)
    {
        try {

            $template = NotificationTemplate::get($event, $replacements);
            
            $data = array_merge([
                'type' => $event,
                'notification' => $template
            ], $additionalData);


            if (isset($notifiable) && $notifiable != null) {
                foreach($userIds as $userId){
                    Notification::create([
                        'user_id' => $userId,
                        'title' => $template['title'],
                        'body' => $template['body'],
                        'event_type' => $event,
                        'receiver' => $template['receiver'],
                        'room_name' => $template['room_name'] ?? null,
                        'data' => $data,
                        'notification_type' => $template['notification_type'],
                        'notifiable_type'=>$notifiable['notifiable'],
                        'notifiable_id'=>$notifiable['notifiable_id']
                    ]);

                }
            }
    
            if ($template['receiver'] === 'room') {
                return $this->sendToRoom($template, $data);
            }

            return $this->sendToRecipients($template, $userIds, $data);

        } catch (\Exception $e) {
            Log::error('Notification error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function sendToRecipients($notification, $recipients, $data)
    {
        if (!is_array($recipients)) {
            $recipients = [$recipients];
        }

        $users = User::whereIn('id', $recipients)
            ->with(['settings', 'profile'])
            ->get();

        $pushRecipients = [];
        $smsRecipients = [];
        $emailRecipients = [];
        $successCount = 0;

        // Group users by their notification preferences
        foreach ($users as $user) {
            $notification_pref = $user->settings->settings['user_management']['notification_preferences'] ?? null;
            if ($notification_pref) {
                if ($notification_pref['push_notifications']==true ?? false) {
                    $pushRecipients[] = $user->id;
                }
                if ($notification_pref['sms']==true ?? false) {
                    if ($user->profile && $user->profile->phone_number) {
                        $smsRecipients[] = $user->profile->phone_number;
                    }
                }
                if ($notification_pref['email']==true ?? false) {
                    if ($user->email) {
                        $emailRecipients[] =  $user->email;
                    }
                }
            }
        }

        if (!empty($pushRecipients)) {
            $success = match ($notification['receiver']) {
                'patient', 'doctor', 'caregiver' => $this->fcm->sendToUser($pushRecipients, $notification['title'], $notification['body'], $data),
                default => false
            };
            if ($success) $successCount++;
        }

        if (!empty($smsRecipients)) {
            try {
                SendSMSJob::dispatch($smsRecipients, $notification['body'], true);
                $successCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send notification SMS: ' . $e->getMessage());
            }
        }

        if (!empty($emailRecipients)) {
            try {
                Log::info('Sending email notification to: ' . implode(', ', $emailRecipients));
                Mail::to($emailRecipients)->send(
                    new NotificationMail(
                        $notification['title'],
                        $notification['body'],
                        $data
                    )
                );
                $successCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send notification email: ' . $e->getMessage());
            }
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