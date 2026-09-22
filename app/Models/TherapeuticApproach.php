<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapeuticApproach extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_therapeutic_approach');
    }
}
