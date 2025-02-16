<?php

namespace App\Http\Controllers\Notification\Test;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class NotificationTestController extends Controller
{
    protected $notificationService;
    protected $testData;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->loadTestData();
    }

    protected function loadTestData()
    {
        $path = base_path('notification_test_data.json');
        if (!File::exists($path)) {
            throw new \RuntimeException('Notification test data file not found');
        }
        $this->testData = json_decode(File::get($path), true);
    }

    public function testTokenNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event' => 'required|string',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $event = $request->input('event');
        
        if (!isset($this->testData[$event])) {
            return response()->json([
                'success' => false,
                'message' => "No test data found for event: {$event}"
            ], 404);
        }

        try {
            $testData = $this->testData[$event];
            $recipients = [$request->input('user_id')];
            $replacements = $this->getReplacements($event, $testData);

            $result = $this->notificationService->send(
                $event,
                $recipients,
                $replacements,
                $testData
            );

            return response()->json([
                'success' => true,
                'message' => 'Test token notification sent successfully',
                'data' => [
                    'event' => $event,
                    'user_id' => $request->input('user_id'),
                    'result' => $result
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testRoomNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event' => 'required|string',
            'room' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $event = $request->input('event');
        
        if (!isset($this->testData[$event])) {
            return response()->json([
                'success' => false,
                'message' => "No test data found for event: {$event}"
            ], 404);
        }

        try {
            $testData = $this->testData[$event];
            $replacements = $this->getReplacements($event, $testData);

            $result = $this->notificationService->send(
                $event,
                null,
                $replacements,
                $testData
            );

            return response()->json([
                'success' => true,
                'message' => 'Test room notification sent successfully',
                'data' => [
                    'event' => $event,
                    'room' => $request->input('room'),
                    'result' => $result
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function getReplacements($event, array $testData)
    {
        switch ($event) {
            case 'medication_reminder':
                return [
                    'Medication Name' => $testData['payload']['medication']['medication_name'],
                    'Dosage Quantity' => $testData['payload']['medication']['dosage_quantity'],
                    'Dosage Strength' => $testData['payload']['medication']['dosage_strength'],
                ];

            case 'missed_medication_room':
                return [
                    'Patient_Id' => $testData['payload']['patient']['id'],
                    'Patient Name' => $testData['payload']['patient']['name'],
                    'Medication Name' => $testData['payload']['medication']['medication_name']
                ];

            case 'emergency_alert':
                return [
                    'Patient Name' => $testData['payload']['patient']['name'],
                    'Alert Type' => ucfirst(str_replace('_', ' ', $testData['payload']['alert_type'])),
                    'Location' => $testData['payload']['location']
                ];

            case 'patient_vitals_room':
                return [
                    'Patient_Id' => $testData['payload']['patient_id'],
                    'Patient Name' => $testData['payload']['patient']['name'],
                    'Blood Pressure' => $testData['payload']['blood_pressure'],
                    'Heart Rate' => $testData['payload']['heart_rate']
                ];

            default:
                throw new \RuntimeException("Unsupported event type: {$event}");
        }
    }

    public function listEvents()
    {

        $templates = null;


        if ($templates === null) {
            $path = base_path('notification.json');
            if (!File::exists($path)) {
                throw new \RuntimeException('Notification templates file not found');
            }
            $templates = json_decode(File::get($path), true);

            $template = collect($templates);

            return response()->json([
                'success' => true,
                'data' => $template
            ]);
        }
    }
}
