<?php

namespace App\Models;

use App\Enums\ConsultationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialty_id',
        'license_number',
        'license_document',
        'license_status',
        'bio',
        'education',
        'experience_years',
        'languages',
        'consultation_types',
        'session_duration',
        'consultation_price',
        'currency',
        'address',
        'city',
        'state',
        'rating',
        'total_reviews',
        'total_consultations',
    ];

    protected $casts = [
        'consultation_types'  => 'array',
        'consultation_price'  => 'decimal:2',
        'rating'              => 'decimal:2',
        'experience_years'    => 'integer',
        'total_reviews'       => 'integer',
        'total_consultations' => 'integer',
    ];

    // Relaciones
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialty(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function appointments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('license_status', 'verified');
    }

    public function scopeBySpecialty($query, int $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    // Helpers
    public function isVerified(): bool
    {
        return $this->license_status === 'verified';
    }

    public function offersVideoCall(): bool
    {
        return in_array('videollamada', $this->consultation_types ?? []);
    }

    public function offersChat(): bool
    {
        return in_array('chat', $this->consultation_types ?? []);
    }

    public function offersPresencial(): bool
    {
        return in_array('presencial', $this->consultation_types ?? []);
    }
}
