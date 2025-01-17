<?php

namespace App\Service\Sms;

use AfricasTalking\SDK\AfricasTalking;
use Exception;

class SendSms
{
    // Set your app credentials
    private $username;
    private $apiKey;

    // SDK instance
    private $AT;

    public function __construct()
    {
        // Load credentials from environment variables
        $this->username = env('AFRICAS_TALKING_API_USERNAME');
        $this->apiKey = env('AFRICAS_TALKING_API_KEY');

        // Initialize the SDK
        $this->AT = new AfricasTalking($this->username, $this->apiKey);
    }

    public function send($number)
    {
        $sms = $this->AT->sms();

        $recipients = $number;

        $message = "I'm a lumberjack and it's ok, I sleep all night and I work all day";

        // $from = "sandbox";

        try {
            $data = [
                'to'      => $recipients,
                'message' => $message,
                // 'from'    => $from
            ];

            $result = $sms->send($data);

            return $result;
        } catch (Exception $e) {
            return $e;
        }
    }
}
