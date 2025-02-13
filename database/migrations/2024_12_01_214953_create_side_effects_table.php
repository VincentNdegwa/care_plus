<?php

use Carbon\Carbon;
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
        Schema::create('side_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId("medication_id")->constrained('medications')->onDelete('cascade');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->dateTime("datetime")->default(Carbon::now());
            $table->string("side_effect");
            $table->enum("severity", ['Mild','Moderate','Severe']);
            $table->string("duration")->nullable();
            $table->string("notes")->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('side_effects');
    }
};
