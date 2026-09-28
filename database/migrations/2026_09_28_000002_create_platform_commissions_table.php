<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();

            // Montos
            $table->decimal('appointment_amount',   8, 2);
            $table->decimal('platform_percentage',  5, 2); // % de comisión aplicado
            $table->decimal('platform_commission',  8, 2); // monto comisión plataforma
            $table->string('gateway', 20);                 // stripe | mercadopago
            $table->decimal('gateway_fee_percentage', 5, 2)->default(0);
            $table->decimal('gateway_fee_fixed',      8, 2)->default(0);
            $table->decimal('gateway_commission',     8, 2); // monto comisión gateway
            $table->decimal('doctor_net_amount',      8, 2); // lo que le toca al doctor
            $table->string('currency', 3)->default('MXN');

            // Estado de cobro
            $table->enum('status', ['pending', 'collected', 'disputed'])->default('pending');
            $table->timestamp('collected_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['doctor_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_commissions');
    }
};
