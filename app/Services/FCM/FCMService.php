<?php

namespace App\Services\FCM;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Psr7\Request;

class FCMService
{
    protected $baseUrl = 'https://fcm.googleapis.com/v1/projects/';
    protected $projectId;
    protected $accessToken;
    protected $certPath;
    protected $guzzle;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
        $this->certPath = base_path('cacert.pem');
        $this->guzzle = new Client(['verify' => false]);
        $this->accessToken = $this->getAccessToken();
    }

    public function getAccessToken()
    {
        $credentialsPath = config('services.fcm.credentials_path');
        
        if (!str_starts_with($credentialsPath, '/')) {
            $credentialsPath = base_path($credentialsPath);
        }

        if (!file_exists($credentialsPath)) {
            Log::error('Firebase credentials file not found: ' . $credentialsPath);
            throw new \RuntimeException('Firebase credentials file not found');
        }

        $credentialsFile = json_decode(
            file_get_contents($credentialsPath), 
            true
        );

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $credentialsFile
        );

        // Use Guzzle client directly
        $httpHandler = function ($request) {
            $options = [
                'headers' => $request->getHeaders(),
                'body' => $request->getBody(),
                'verify' => false
            ];
            
            return $this->guzzle->send($request, $options);
        };

        $token = $credentials->fetchAuthToken($httpHandler);
        return $token['access_token'];
    }

    public function sendToToken($token, $title, $body, $data = [])
    {
        // Convert all data values to strings and encode the entire payload as a single string
        // $formattedData = [];
        // foreach ($data as $key => $value) {
        //     $formattedData[$key] = is_string($value) ? $value : json_encode($value);
        // }

        $message = [
            'message' => [
                'token' => $token,
                "priority"=> "high",
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'data' => json_encode($data)
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'medication_notifications',
                        'notification_priority' => 'PRIORITY_HIGH'
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ]
                ]
            ]
        ];

        try {
            Log::info('FCM Request Payload:', ['payload' => $message]);

            $response = $this->guzzle->post(
                $this->baseUrl . $this->projectId . '/messages:send',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $message,
                    'verify' => false
                ]
            );

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            
            Log::info('FCM Response', [
                'status' => $statusCode,
                'body' => $body
            ]);

            if ($statusCode !== 200) {
                Log::error('FCM Error', [
                    'status' => $statusCode,
                    'body' => $body,
                    'token' => $token
                ]);
                
                if ($statusCode === 404 || 
                    str_contains($body, 'registration-token-not-registered')) {
                    DeviceToken::where('token', $token)->delete();
                }
                
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('FCM Send Error', [
                'message' => $e->getMessage(),
                'token' => $token,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function isJson($string) {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function sendToUser($userId, $title, $body, $data = [])
    {
        $tokens = DeviceToken::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            Log::info("No active device tokens found for user: $userId");
            return false;
        }

        $successCount = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $successCount++;
            }
        }

        return $successCount > 0;
    }

    public function testSend()
    {
        $token = "fmDOf84aQdeOWGqtBO9gXb:APA91bE4TD1tqiSG4RL79bmERD0W-hBeLkYlMv5wPsNcQHeDflZlcWulEoqydUDurYCRzhMOeMzUHGWIk7_wvEueEpdRzHnWNeskotokofyDohfq0i0ONJs";
        
        // Clean any whitespace
        $token = trim($token);
        
        Log::info('Starting FCM test send');
        
        // All data values must be strings
        $result = $this->sendToToken(
            $token,
            "Test Notification",
            "This is a test notification from Care Plus",
            [
                "type" => "test",
                "timestamp" => (string) now()->timestamp 
            ]
        );
        
        if ($result) {
            Log::info('FCM test send successful');
            return [
                'success' => true,
                'message' => 'Test notification sent successfully'
            ];
        } else {
            Log::error('FCM test send failed');
            return [
                'success' => false,
                'message' => 'Failed to send test notification'
            ];
        }
    }
} 




// $fcm = new FCMService()
// $fcm->getAccessToken()
// $fcm->sendToToken(
//     "fmDOf84aQdeOWGqtBO9gXb:APA91bE4TD1tqiSG4RL79bmERD0W-hBeLkYlMv5wPsNcQHeDflZlcWulEoqydUDurYCRzhMOeMzUHGWIk7_wvEueEpdRzHnWNeskotokofyDohfq0i0ONJs
// ",
//     "Test Title",
//     "Test Message",
//     ["key" => "value"]
// );