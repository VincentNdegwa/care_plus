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
        $schedule = MedicationSchedule::create([
            'medication_id' => 5,
            'patient_id' => 2,
            // 'medication_id'=> 1,
            // 'patient_id'=> 1,
            'dose_time' => '2025-02-13 05:24:00',
            'status' => 'Pending'
        ]);

        $this->schedule = $schedule;
    }

    public function broadcastOn(): array
    {

        $channel = 'medication.take.' . $this->schedule->patient_id;
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
        return $this->schedule->toArray();
    }
}
