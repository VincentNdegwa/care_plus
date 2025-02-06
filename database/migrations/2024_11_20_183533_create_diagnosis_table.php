<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('diagnosis_name');
            $table->text('description')->nullable();
            $table->text('symptoms')->nullable();
            $table->date('date_diagnosed');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->onDelete('set null');
            $table->string('medication_name');
            $table->string('dosage_quantity');
            $table->string("dosage_strength");  //500mg
            $table->foreignId('form_id')->nullable()->constrained('medication_forms')->onDelete('set null'); // Medication form
            $table->foreignId("route_id")->nullable()->constrained('medication_routes')->onDelete('set null'); // eg Oral
            $table->string('frequency'); // Frequency (e.g., "2 times per day")
            $table->string('duration')->nullable(); // Total duration (e.g., "7 days")
            $table->dateTime('prescribed_date')->default(Carbon::now()->format('Y-m-d H:i:s'));
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->foreignId('caregiver_id')->nullable()->constrained('caregivers')->onDelete('set null');
            $table->integer('stock')->nullable(); // Total stock
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("medications");
        Schema::dropIfExists('diagnoses');
    }
};
