<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained();

            // Identidad profesional
            $table->string('license_number')->unique();
            $table->string('license_document')->nullable();
            $table->enum('license_status', ['pending', 'verified', 'rejected'])->default('pending');

            // Perfil público
            $table->text('bio')->nullable();
            $table->string('education')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->string('languages')->default('Español');

            // Configuración de consulta
            $table->json('consultation_types')->nullable();
            $table->unsignedSmallInteger('session_duration')->default(60);
            $table->decimal('consultation_price', 8, 2);
            $table->string('currency', 3)->default('MXN');

            // Ubicación
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();

            // Métricas
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_consultations')->default(0);

            // Índices para búsquedas frecuentes
            $table->index('license_status');    // filtrar doctores verificados
            $table->index('rating');            // ordenar por calificación
            $table->index('consultation_price'); // filtrar por precio
            $table->index('city');              // buscar por ciudad
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
