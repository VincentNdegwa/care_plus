<?php

namespace App\Events;

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

    public function __construct($schedule)
    {
        Log::info("MedicationTake event constructed", ['schedule_id' => $schedule->id]);
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

    public function broadcastWith(): array
    {
        return $this->schedule->toArray();
    }
}
