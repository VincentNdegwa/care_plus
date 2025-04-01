<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MoveSMS\MoveSMSService;
use Illuminate\Support\Facades\Log;

class SendSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 20;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    private $phoneNumbers;
    private $message;
    private $isBulk;

    /**
     * Create a new job instance.
     *
     * @param string|array $phoneNumbers Single phone number or array of phone numbers
     * @param string $message The message to send
     * @param bool $isBulk Whether this is a bulk SMS or not
     */
    public function __construct($phoneNumbers, string $message, bool $isBulk = false)
    {
        $this->phoneNumbers = $phoneNumbers;
        $this->message = $message;
        $this->isBulk = $isBulk;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MoveSMSService $smsService)
    {
        try {
            if ($this->isBulk) {
                $response = $smsService->sendBulkSMS($this->phoneNumbers, $this->message);
            } else {
                $response = $smsService->sendSMS($this->phoneNumbers, $this->message);
            }

            Log::info('SMS Job completed', [
                'to' => $this->phoneNumbers,
                'response' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('SMS Job failed', [
                'to' => $this->phoneNumbers,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
