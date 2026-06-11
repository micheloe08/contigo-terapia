<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('patient_id')->constrained();

            // Tiempo
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            // Tipo y estado
            $table->enum('type', ['presencial', 'videollamada', 'chat']);
            $table->enum('status', [
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'cancelled',
                'no_show',
            ])->default('pending');

            // Motivo y notas
            $table->text('reason')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Videollamada
            $table->string('room_url')->nullable();
            $table->string('room_name')->nullable();

            // Precio al momento de agendar
            $table->decimal('price', 8, 2);
            $table->string('currency', 3)->default('MXN');

            // Índices
            $table->index(['doctor_id', 'starts_at']);
            $table->index(['patient_id', 'starts_at']);
            $table->index('status');
            $table->index('type');
            $table->index('starts_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
