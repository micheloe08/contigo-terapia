<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->constrained();

            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('plan');
            $table->decimal('price', 8, 2);
            $table->string('currency', 3)->default('MXN');

            $table->enum('status', [
                'active',
                'cancelled',
                'past_due',
                'paused',
            ])->default('active');

            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Índices
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index('status');
            $table->index('current_period_end');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
