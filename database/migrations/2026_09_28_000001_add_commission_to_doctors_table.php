<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campo de comisión configurable por terapeuta
        Schema::table('doctors', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)
                  ->default(15.00)
                  ->after('membership_expires_at')
                  ->comment('Porcentaje de comisión de la plataforma (0-100)');
            $table->boolean('commission_override')
                  ->default(false)
                  ->after('commission_percentage')
                  ->comment('Si true, ignora el porcentaje global y usa commission_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['commission_percentage', 'commission_override']);
        });
    }
};
