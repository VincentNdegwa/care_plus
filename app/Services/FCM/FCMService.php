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

    protected function getAccessToken()
    {
        $credentialsPath = config('services.fcm.credentials_path');
        
        if (!str_starts_with($credentialsPath, '/')) {
            $credentialsPath = base_path($credentialsPath);
        }

        if (!file_exists($credentialsPath)) {
            Log::error('Firebase credentials file not found: ' . $credentialsPath);
            throw new \RuntimeException('Firebase credentials file not found');
        }

        $credentialsFile = json_decode(file_get_contents($credentialsPath), true);
        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $credentialsFile
        );

        $httpHandler = function ($request) {
            return $this->guzzle->send($request, [
                'headers' => $request->getHeaders(),
                'body' => $request->getBody(),
                'verify' => false
            ]);
        };

        $token = $credentials->fetchAuthToken($httpHandler);
        return $token['access_token'];
    }

    protected function buildDataMessage($recipient, $data)
    {
        $message = [
            'message' => [
                'data' => [
                    'data' => json_encode($data)
                ],
            ]
        ];

        if (isset($recipient['token'])) {
            $token = trim($recipient['token']);
            $message['message']['token'] = $token;
        } elseif (isset($recipient['topic'])) {
            $message['message']['topic'] = $recipient['topic'];
        }

        return $message;
    }

    protected function buildNotificationMessage($recipient, $event, $priority, $data)
    {
        $message = [
            'message' => [
                'notification' => [
                    'title' => $event['title'] ?? 'Notification',
                    'body' => $event['body'] ?? '',
                ],
                'data' => [
                    'data' => json_encode($data)
                ],
                'android' => [
                    'priority' => $priority,
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

        if (isset($recipient['token'])) {
            $message['message']['token'] = $recipient['token'];
        } elseif (isset($recipient['topic'])) {
            $message['message']['topic'] = $recipient['topic'];
        }

        return $message;
    }

    protected function sendMessage($message, $recipientInfo = [])
    {
        try {
            // Validate the message structure
            if (!isset($message['message']) || 
                (!isset($message['message']['token']) && !isset($message['message']['topic']))) {
                Log::error('FCM Invalid message structure', ['message' => $message]);
                return 'Invalid message structure: '.json_encode($message);
            }

            $token = $message['message']['token'] ?? null;
            if ($token) {
                // Clean the token
                $token = trim($token);
                $message['message']['token'] = $token;
            }

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
                'body' => $body,
                'token' => $token
            ]);

            if ($statusCode !== 200) {
                Log::error('FCM Error', array_merge([
                    'status' => $statusCode,
                    'body' => $body,
                    'token' => $token,
                    'projectId' => $this->projectId
                ], $recipientInfo));
                
                if (isset($recipientInfo['token']) && 
                    ($statusCode === 404 || str_contains($body, 'registration-token-not-registered'))) {
                    Log::info('Deleting invalid token', ['token' => $recipientInfo['token']]);
                    DeviceToken::where('token', $recipientInfo['token'])->delete();
                }
                
                return $body;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('FCM Send Error', array_merge([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'projectId' => $this->projectId
            ], $recipientInfo));

            return array_merge([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'projectId' => $this->projectId
            ], $recipientInfo);
        }
    }

    public function sendToToken($token, $title, $body, $data = [])
    {
        $message = $this->buildDataMessage(['token' => $token], $data);
        return $this->sendMessage($message, ['token' => $token]);
    }

    public function sendToRoom($room, $title, $body, $data = [])
    {
        $message = $this->buildDataMessage(['topic' => $room], $data);
        return $this->sendMessage($message, ['room' => $room]);
    }

    public function sendToUser($userId, $title, $body, $data = [])
    {
        $tokens = DeviceToken::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            Log::info("No active device tokens found for user ID: $userId");
            return false;
        }

        $successCount = 0;
        foreach ($tokens as $token) {
            $token = trim($token);
            if (empty($token)) continue;

            if ($this->sendToToken($token, $title, $body, $data)) {
                $successCount++;
            }
        }

        return $successCount > 0;
    }

    public function sendNotification($token = null, $room = null, $event, $priority = 'high', $data = [])
    {
        if (!$token && !$room) {
            Log::error('FCM Error: Neither token nor room provided');
            return "Neither token nor room provided";
        }

        $recipient = $token ? ['token' => $token] : ['topic' => $room];
        $message = $this->buildNotificationMessage($recipient, $event, $priority, $data);
        
        return $this->sendMessage($message, [
            'token' => $token,
            'room' => $room,
            'data' => $data
        ]);
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

// $message = [
//     'message' => [
//         'token' => $token,
//         'notification' => [
//             'title' => $title,
//             'body' => $body,
//         ],
//         'data' => [
//             'data' => json_encode($data)
//         ],
//         'android' => [
//             'priority' => 'high',
//             'notification' => [
//                 'channel_id' => 'medication_notifications',
//                 'notification_priority' => 'PRIORITY_HIGH'
//             ]
//         ],
//         'apns' => [
//             'payload' => [
//                 'aps' => [
//                     'sound' => 'default',
//                     'badge' => 1
//                 ]
//             ]
//         ]
//     ]
// ];