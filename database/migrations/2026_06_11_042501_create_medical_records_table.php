<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('patient_id')->constrained();

            $table->text('diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->text('prescription')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->date('next_appointment_suggested')->nullable();

            // Índices
            $table->index(['patient_id', 'created_at']);
            $table->index(['doctor_id', 'created_at']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
