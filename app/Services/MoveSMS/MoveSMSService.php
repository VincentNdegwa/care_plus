<?php

namespace App\Services\MoveSMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoveSMSService
{
    private $baseUrl;
    private $username;
    private $apiKey;
    private $senderId;
    private $messageType;
    private $deliveryReport;

    public function __construct()
    {
        $this->baseUrl = 'https://sms.movesms.co.ke/api/compose';
        $this->username = env('MOVE_SMS_USERNAME');
        $this->apiKey = env('MOVE_SMS_API_KEY');
        $this->senderId = env('MOVE_SMS_SENDER_ID');
        $this->messageType = env('MOVE_SMS_MSG_TYPE', 5);
        $this->deliveryReport = env('MOVE_SMS_DELIVERY_REPORT', 0);
    }

    /**
     * Send SMS to a single recipient
     * 
     * @param string $phoneNumber The recipient's phone number (format: 254XXXXXXXXX)
     * @param string $message The message to send
     * @return array Response from the SMS service
     */
    public function sendSMS($phoneNumber, $message)
    {
        try {
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);

            $response = Http::get($this->baseUrl, [
                'username' => $this->username,
                'api_key' => $this->apiKey,
                'sender' => $this->senderId,
                'to' => $phoneNumber,
                'message' => $message,
                'msgtype' => $this->messageType,
                'dlr' => $this->deliveryReport
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'to' => $phoneNumber,
                    'response' => $response->json()
                ]);

                // return [
                //     'error' => false,
                //     'message' => 'SMS sent successfully',
                //     'data' => $response->json()
                // ];
            }

            Log::error('Failed to send SMS', [
                'to' => $phoneNumber,
                'response' => $response->body()
            ]);

            // return [
            //     'error' => true,
            //     'message' => 'Failed to send SMS',
            //     'details' => $response->body()
            // ];

        } catch (\Exception $e) {
            Log::error('SMS sending error', [
                'to' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            // return [
            //     'error' => true,
            //     'message' => 'SMS sending error',
            //     'details' => $e->getMessage()
            // ];
        }
    }

    /**
     * Send SMS to multiple recipients
     * 
     * @param array $phoneNumbers Array of phone numbers
     * @param string $message The message to send
     * @return array Response from the SMS service
     */
    public function sendBulkSMS(array $phoneNumbers, $message)
    {
        try {
            // Format phone numbers
            $phoneNumbers = array_map([$this, 'formatPhoneNumber'], $phoneNumbers);
            
            $response = Http::get($this->baseUrl, [
                'username' => $this->username,
                'api_key' => $this->apiKey,
                'sender' => $this->senderId,
                'to' => implode(',', $phoneNumbers),
                'message' => $message,
                'msgtype' => $this->messageType,
                'dlr' => $this->deliveryReport
            ]);

            if ($response->successful()) {
                Log::info('Bulk SMS sent successfully', [
                    'to' => $phoneNumbers,
                    'response' => $response->json()
                ]);

                // return [
                //     'error' => false,
                //     'message' => 'Bulk SMS sent successfully',
                //     'data' => $response->json()
                // ];
            }

            Log::error('Failed to send bulk SMS', [
                'to' => $phoneNumbers,
                'response' => $response->body()
            ]);

            // return [
            //     'error' => true,
            //     'message' => 'Failed to send bulk SMS',
            //     'details' => $response->body()
            // ];

        } catch (\Exception $e) {
            Log::error('Bulk SMS sending error', [
                'to' => $phoneNumbers,
                'error' => $e->getMessage()
            ]);

            // return [
            //     'error' => true,
            //     'message' => 'Bulk SMS sending error',
            //     'details' => $e->getMessage()
            // ];
        }
    }

    /**
     * Format phone number to required format (254XXXXXXXXX)
     */
    private function formatPhoneNumber($number)
    {
        // Remove any spaces, dashes, or plus signs
        $number = preg_replace('/[^0-9]/', '', $number);

        // If number starts with 0, replace it with 254
        if (strlen($number) === 10 && substr($number, 0, 1) === '0') {
            $number = '254' . substr($number, 1);
        }

        // If number starts with 7 or 1, add 254
        if (strlen($number) === 9 && (substr($number, 0, 1) === '7' || substr($number, 0, 1) === '1')) {
            $number = '254' . $number;
        }

        return $number;
    }
} 