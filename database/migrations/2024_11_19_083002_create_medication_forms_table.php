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
        Schema::create('medication_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer("patient_id")->nullable();
            $table->timestamps();
        });

        Schema::create('medication_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer("patient_id")->nullable();
            $table->timestamps();
        });
        Schema::create('medication_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('medication_frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('frequency'); // column for storing frequency names
            $table->timestamps(); // for created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_forms');
        Schema::dropIfExists('medication_units');
        Schema::dropIfExists('medication_routes');
        Schema::dropIfExists('medication_frequencies');
    }
};
