<?php

namespace App\Events;

use App\Models\Schedules\MedicationSchedule;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EventTest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $schedule;

    public function __construct()
    {
        $schedule = [
            "id" => 26,
            "medication_id" => 1,
            "patient_id" => 1,
            "dose_time" => "2025-02-08 15:11:21",
            "processed_at" => "2025-02-08 15:12:04",
            "status" => "Pending",
            "taken_at" => null,
            "second_notification_sent" => 1,
            "created_at" => "2025-02-07T18:11:21.000000Z",
            "updated_at" => "2025-02-08T17:12:06.000000Z",
            "medication" => [
                "id" => 1,
                "patient_id" => 1,
                "diagnosis_id" => null,
                "medication_name" => "Panadols",
                "dosage_quantity" => "2",
                "dosage_strength" => "500mg",
                "form_id" => 3,
                "route_id" => 2,
                "frequency" => "Three times a day",
                "duration" => "3 days",
                "prescribed_date" => "2025-02-07 17:33:25",
                "doctor_id" => null,
                "caregiver_id" => null,
                "stock" => 20,
                "active" => 1
            ]
        ];

        $this->schedule = $schedule;
    }

    public function broadcastOn(): array
    {
        $channel = 'medication.take.' . $this->schedule['patient_id'];
        Log::info("Broadcasting on channel", ['channel' => $channel]);
        return [
            new PrivateChannel($channel),
        ];
    }

    public function broadcastAs(): string
    {
        return 'medication.take';
    }

    public function broadcastWith(): array
    {
        return $this->schedule;
    }
}
