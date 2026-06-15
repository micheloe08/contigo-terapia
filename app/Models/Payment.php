<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'stripe_payment_intent_id',
        'mercadopago_payment_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'status'           => PaymentStatus::class,
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
    ];

    // Relaciones
    public function appointment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', PaymentStatus::Paid);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::Pending);
    }

    // Helpers
    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status'  => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
