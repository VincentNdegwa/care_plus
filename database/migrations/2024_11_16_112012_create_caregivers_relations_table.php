<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('caregivers_relations', function (Blueprint $table) {
            $table->id();
            $table->string("relation")->default('nurse');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('caregiver_id')->constrained('caregivers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caregivers_relations');
    }
};
