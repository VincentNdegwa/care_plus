<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Get filtered notifications for the authenticated user.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'notification_type' => 'nullable|in:Medication,Health,System',
                'status' => 'nullable|in:Sent,Delivered,Failed',
                'is_read' => 'nullable|boolean',
                'receiver_type' => 'nullable|in:patient,doctor,caregiver,room',
                'event_type' => 'nullable|string',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|in:created_at,updated_at,read_at',
                'sort_direction' => 'nullable|in:asc,desc'
            ]);

            $query = Notification::forUser(Auth::id());

            if ($request->filled('notification_type')) {
                $query->where('notification_type', $request->notification_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('is_read')) {
                $query->where('is_read', $request->boolean('is_read'));
            }

            if ($request->filled('receiver_type')) {
                $query->where('receiver_type', $request->receiver_type);
            }

            if ($request->filled('event_type')) {
                $query->where('event_type', $request->event_type);
            }

    
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->input('per_page', 15);
            $notifications = $query->paginate($perPage);

            return response()->json([
                'error' => false,
                'notifications' => $notifications,
                'filters' => [
                    'notification_types' => ['Medication', 'Health', 'System'],
                    'statuses' => ['Sent', 'Delivered', 'Failed'],
                    'receiver_types' => ['patient', 'doctor', 'caregiver', 'room']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to fetch notifications',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark multiple notifications as read.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'required|integer|exists:notifications,id'
            ]);

            $notifications = Notification::where('user_id', Auth::id())
                ->whereIn('id', $request->notification_ids)
                ->get();

            if ($notifications->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'No valid notifications found'
                ], 404);
            }

            foreach ($notifications as $notification) {
                $notification->markAsRead();
            }

            return response()->json([
                'error' => false,
                'message' => count($notifications) . ' notifications marked as read successfully',
                'updated_ids' => $notifications->pluck('id')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to mark notifications as read',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete multiple notifications.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'required|integer|exists:notifications,id'
            ]);

            $deletedCount = Notification::where('user_id', Auth::id())
                ->whereIn('id', $request->notification_ids)
                ->delete();

            return response()->json([
                'error' => false,
                'message' => "{$deletedCount} notifications deleted successfully."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to delete notifications',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new notification (for testing or manual triggering).
     */
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
            'event_type' => 'required|string',
            'receiver_type' => 'required|in:patient,doctor,caregiver,room',
            'notifiable_type' => 'required|string',
            'notifiable_id' => 'required|integer',
            'data' => 'nullable|array',
            'notification_type' => 'required|string'
        ]);

        Notification::create($validatedData);

        return response()->json([
            'error' => false,
            'message' => 'Notification created successfully.'
        ]);
    }
}
