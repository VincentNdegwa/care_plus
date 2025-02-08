<?php

namespace App\Events;

use App\Models\Schedules\MedicationSchedule;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MedicationTake implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $schedule;
    public $medicationSchedule;

    public function __construct($schedule)
    {
        Log::info("MedicationTake event constructed", ['schedule_id' => $schedule->id]);
        $this->schedule = $schedule;
        $this->medicationSchedule = MedicationSchedule::with('medication')->find($schedule->id);
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
        return $this->medicationSchedule->toArray();
    }
}
