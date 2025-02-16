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

    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event' => 'required|string',
            'token' => 'required_without:room|string',
            'room' => 'required_without:token|string'
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
            $result = $this->sendNotification($event, $request, $testData);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function sendNotification($event, Request $request, array $testData)
    {
        $recipients = [];
        $replacements = [];
        
        if ($testData['type'] === 'token' && $request->has('token')) {
            $recipients = DeviceToken::where("token", $request->input('token'))->pluck('user_id')->toArray();
        }

        // Build replacements based on event type
        switch ($event) {
            case 'medication_reminder':
                $replacements = [
                    'Medication Name' => $testData['payload']['medication']['medication_name'],
                    'Dosage Quantity' => $testData['payload']['medication']['dosage_quantity'],
                    'Dosage Strength' => $testData['payload']['medication']['dosage_strength'],
                ];
                break;

            case 'missed_medication_room':
                $replacements = [
                    'Patient_Id' => $testData['payload']['patient']['id'],
                    'Patient Name' => $testData['payload']['patient']['name'],
                    'Medication Name' => $testData['payload']['medication']['medication_name']
                ];
                break;

            case 'emergency_alert':
                $replacements = [
                    'Patient Name' => $testData['payload']['patient']['name'],
                    'Alert Type' => ucfirst(str_replace('_', ' ', $testData['payload']['alert']['type'])),
                    'Location' => $testData['payload']['location']['area']
                ];
                break;

            case 'patient_vitals_room':
                $replacements = [
                    'Patient_Id' => $testData['payload']['patient']['id'],
                    'Patient Name' => $testData['payload']['patient']['name'],
                    'Blood Pressure' => $testData['payload']['vitals']['blood_pressure'],
                    'Heart Rate' => $testData['payload']['vitals']['heart_rate']
                ];
                break;

            default:
                throw new \RuntimeException("Unsupported event type: {$event}");
        }

        return $this->notificationService->send(
            $event,
            $recipients,
            $replacements,
            $testData['data']
        );
    }

    public function listEvents()
    {
        return response()->json([
            'success' => true,
            'events' => array_keys($this->testData)
        ]);
    }
}
