<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_snoozes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_schedule_id')->constrained('medication_schedules')->onDelete('cascade');
            $table->dateTime('snooze_time');
            $table->enum('status', ['Pending', 'Snoozed', 'Dismissed'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_snoozes');
    }
}; 