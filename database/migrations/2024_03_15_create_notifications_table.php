<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Notification Content
            $table->string('title');
            $table->text('body');
            $table->string('event_type'); // matches event in notification.json (e.g., medication_reminder, new_diagnosis_notification)
            
            // Target Information
            $table->enum('receiver_type', ['patient', 'doctor', 'caregiver', 'room']);
            $table->string('room_name')->nullable(); // For room-specific notifications
            
            // Related Data References
            $table->morphs('notifiable'); // For polymorphic relations (diagnosis_id, medication_id, etc)
            $table->json('data')->nullable(); // Additional data payload
            
            // Status and Tracking
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->enum('status', ['Sent', 'Delivered', 'Failed'])->default('Sent');
            $table->enum('notification_type', ['Medication', 'Health', 'System']);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // Allow soft deletes for notification history
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
}; 