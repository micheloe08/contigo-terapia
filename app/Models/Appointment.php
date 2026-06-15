<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'doctor_id',
        'patient_id',
        'starts_at',
        'ends_at',
        'type',
        'status',
        'reason',
        'doctor_notes',
        'cancellation_reason',
        'cancelled_at',
        'room_url',
        'room_name',
        'price',
        'currency',
    ];

    protected $casts = [
        'starts_at'    => 'datetime',
        'ends_at'      => 'datetime',
        'cancelled_at' => 'datetime',
        'status'       => AppointmentStatus::class,
        'price'        => 'decimal:2',
    ];

    // Generar UUID automáticamente
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($appointment) {
            $appointment->uuid = Str::uuid();
        });
    }

    // Relaciones
    public function doctor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function medicalRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', AppointmentStatus::Pending);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', AppointmentStatus::Confirmed);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', AppointmentStatus::Completed);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', [
            AppointmentStatus::Pending,
            AppointmentStatus::Confirmed,
        ])->where('starts_at', '>', now());
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === AppointmentStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === AppointmentStatus::Confirmed;
    }

    public function isCompleted(): bool
    {
        return $this->status === AppointmentStatus::Completed;
    }

    public function isCancelled(): bool
    {
        return $this->status === AppointmentStatus::Cancelled;
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status'              => AppointmentStatus::Cancelled,
            'cancellation_reason' => $reason,
            'cancelled_at'        => now(),
        ]);
    }

    public function getDurationInMinutesAttribute(): int
    {
        return $this->starts_at->diffInMinutes($this->ends_at);
    }
}
