<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Obtener un valor por clave
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    // Establecer un valor por clave
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    // Obtener todas las configuraciones de un grupo
    public static function getGroup(string $group): \Illuminate\Support\Collection
    {
        return static::where('group', $group)
            ->get()
            ->keyBy('key');
    }

    // Obtener solo las públicas
    public static function getPublic(): \Illuminate\Support\Collection
    {
        return static::where('is_public', true)
            ->get()
            ->keyBy('key');
    }
}
