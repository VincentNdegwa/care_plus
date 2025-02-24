<?php

namespace App\Jobs;

use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userIds;
    public $arrayData;
    public $notificationType;
    public $notifiable;

    /**
     * Create a new job instance.
     */
    public function __construct($userIds, $arrayData, $notificationType, $notifiable)
    {
        $this->userIds = $userIds;
        $this->arrayData = $arrayData;
        $this->notificationType = $notificationType;
        $this->notifiable = $notifiable;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        switch ($this->notificationType) {
            case 'new_diagnosis_notification':
                $notificationService->send(
                    $this->notificationType,
                    $this->userIds,
                    [
                        'Doctor Name' => $this->arrayData['doctor']['name'] ?? '',
                        'Diagnosis Name' => $this->arrayData['diagnosis_name'] ?? '',
                    ],
                    [
                        'type' => $this->notificationType,
                        'payload' => $this->arrayData
                    ],
                    $this->notifiable
                );
                break;

            case 'medication_reminder':
                $notificationService->send(
                    $this->notificationType,
                    $this->userIds,
                    [
                        'Medication Name' => $this->arrayData['medication']['medication_name'] ?? '',
                        'Dosage Quantity' => $this->arrayData['medication']['dosage_quantity'] ?? '',
                        'Dosage Strength' => $this->arrayData['medication']['dosage_strength'] ?? ''
                    ],
                    [
                        'type' => $this->notificationType,
                        'payload' => $this->arrayData
                    ],
                    $this->notifiable
                );
                break;
            
            default:
                break;
        }
    }
}
