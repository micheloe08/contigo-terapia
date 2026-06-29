<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week'); // 0=Lun, 6=Dom
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);

            // Bloqueo de días específicos
            $table->date('blocked_date')->nullable();
            $table->string('block_reason')->nullable();

            // Índices
            $table->unique(['doctor_id', 'day_of_week', 'start_time']);
            $table->index(['doctor_id', 'is_available']);
            $table->index('blocked_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
