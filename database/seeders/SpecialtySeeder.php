<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            ['name' => 'Psicología General',       'slug' => 'psicologia-general'],
            ['name' => 'Psiquiatría',               'slug' => 'psiquiatria'],
            ['name' => 'Terapia de Pareja',         'slug' => 'terapia-de-pareja'],
            ['name' => 'Psicología Infantil',       'slug' => 'psicologia-infantil'],
            ['name' => 'Terapia Cognitivo-Conductual', 'slug' => 'terapia-cognitivo-conductual'],
            ['name' => 'Ansiedad y Depresión',      'slug' => 'ansiedad-y-depresion'],
            ['name' => 'Adicciones',                'slug' => 'adicciones'],
            ['name' => 'Duelo y Pérdida',           'slug' => 'duelo-y-perdida'],
        ];

        foreach ($specialties as $specialty) {
            DB::table('specialties')->updateOrInsert(
                ['slug' => $specialty['slug']],
                array_merge($specialty, [
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
