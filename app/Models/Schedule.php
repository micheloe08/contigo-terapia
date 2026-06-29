<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'blocked_date',
        'block_reason',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'blocked_date' => 'date',
        'day_of_week'  => 'integer',
    ];

    const DAYS = [
        0 => 'Lunes',
        1 => 'Martes',
        2 => 'Miércoles',
        3 => 'Jueves',
        4 => 'Viernes',
        5 => 'Sábado',
        6 => 'Domingo',
    ];

    // Relaciones
    public function doctor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                     ->whereNull('blocked_date');
    }

    public function scopeForDay($query, int $day)
    {
        return $query->where('day_of_week', $day);
    }

    // Helpers
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Desconocido';
    }
}
