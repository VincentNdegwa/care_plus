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
            $table->dateTime('processed_at')->nullable();
            $table->enum('status', ['Taken', 'Missed', 'Pending'])->default('Pending');
            $table->dateTime('taken_at')->nullable();
            $table->boolean('second_notification_sent')->default(false);
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
            $table->string('schedules')->nullable();
            $table->string("timezone")->default('Africa/Nairobi');
            $table->boolean('active')->default(true);
            $table->enum('status', ['Stopped', 'Running','Expired'])->default('Running');
            $table->dateTime('stopped_when')->nullable();
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
        Schema::dropIfExists('medication_schedules');
    }
};
