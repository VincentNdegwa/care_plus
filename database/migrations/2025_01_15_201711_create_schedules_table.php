<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{


    public function up()
    {
        Schema::create('medication_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('medications')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->dateTime('dose_time');
            $table->timestamps();
        });

        Schema::create('medication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_schedule_id')->constrained('medication_schedules')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->dateTime("time_scheduled");
            $table->enum('status', ['Taken', 'Missed', 'Pending'])->default('Pending');
            $table->timestamps();
        });

        Schema::create('medication_tracker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('medications')->onDelete('cascade');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->dateTime('next_start_month')->nullable();
            $table->dateTime('stop_date')->nullable();
            $table->string('duration')->nullable();
            $table->string('frequency')->nullable();
            $table->timestamps();
        });

        Schema::create('medication_schedules_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_schedule_id')->constrained('medication_schedules', 'id')->onDelete('cascade')->name('med_schedule_id_foreign');
            $table->string('message');
            $table->enum('status', ['Sent', 'Pending', 'Failed'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('medication_schedules_notifications');
        Schema::dropIfExists('medication_tracker');
        Schema::dropIfExists('medication_logs');
        Schema::dropIfExists('medication_schedules');
    }
};
