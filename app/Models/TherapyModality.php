<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapyModality extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_therapy_modality');
    }
}
