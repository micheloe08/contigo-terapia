<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained();
            $table->foreignId('patient_id')->constrained();

            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('mercadopago_payment_id')->nullable()->unique();

            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('MXN');

            $table->enum('status', [
                'pending',
                'processing',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');

            $table->enum('gateway', ['stripe', 'mercadopago']);
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Índices
            $table->index(['patient_id', 'status']);
            $table->index('status');
            $table->index('gateway');
            $table->index('paid_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
