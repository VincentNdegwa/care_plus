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
        Schema::create('doctors_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId("patient_id")->nullable()->constrained("patients")->onDelete("set null");
            $table->foreignId("doctor_id")->nullable()->constrained("doctors")->onDelete("set null");
            $table->boolean('isMain')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors_relations');
    }
};
