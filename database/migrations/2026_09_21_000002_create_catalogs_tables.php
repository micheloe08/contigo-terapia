<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de enfoques terapéuticos
        Schema::create('therapeutic_approaches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de modalidades de terapia
        Schema::create('therapy_modalities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // ícono de Tabler
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de poblaciones atendidas
        Schema::create('target_populations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pivot: doctor ↔ enfoques (muchos a muchos)
        Schema::create('doctor_therapeutic_approach', function (Blueprint $table) {
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapeutic_approach_id')->constrained()->cascadeOnDelete();
            $table->primary(['doctor_id', 'therapeutic_approach_id']);
        });

        // Pivot: doctor ↔ modalidades
        Schema::create('doctor_therapy_modality', function (Blueprint $table) {
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapy_modality_id')->constrained()->cascadeOnDelete();
            $table->primary(['doctor_id', 'therapy_modality_id']);
        });

        // Pivot: doctor ↔ poblaciones
        Schema::create('doctor_target_population', function (Blueprint $table) {
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_population_id')->constrained()->cascadeOnDelete();
            $table->primary(['doctor_id', 'target_population_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_target_population');
        Schema::dropIfExists('doctor_therapy_modality');
        Schema::dropIfExists('doctor_therapeutic_approach');
        Schema::dropIfExists('target_populations');
        Schema::dropIfExists('therapy_modalities');
        Schema::dropIfExists('therapeutic_approaches');
    }
};
