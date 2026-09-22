<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Enfoques terapéuticos
        $approaches = [
            ['name' => 'Cognitivo-Conductual (TCC)',     'slug' => 'cognitivo-conductual'],
            ['name' => 'Psicoanalítico',                 'slug' => 'psicoanalitico'],
            ['name' => 'Humanista',                      'slug' => 'humanista'],
            ['name' => 'Sistémico',                      'slug' => 'sistemico'],
            ['name' => 'Gestalt',                        'slug' => 'gestalt'],
            ['name' => 'EMDR',                           'slug' => 'emdr'],
            ['name' => 'Mindfulness',                    'slug' => 'mindfulness'],
            ['name' => 'Terapia de Aceptación y Compromiso (ACT)', 'slug' => 'act'],
            ['name' => 'Terapia Dialéctica Conductual (DBT)', 'slug' => 'dbt'],
            ['name' => 'Terapia Narrativa',              'slug' => 'narrativa'],
            ['name' => 'Psicología Positiva',            'slug' => 'psicologia-positiva'],
            ['name' => 'Terapia Breve Centrada en Soluciones', 'slug' => 'centrada-en-soluciones'],
        ];

        foreach ($approaches as $item) {
            DB::table('therapeutic_approaches')->updateOrInsert(
                ['slug' => $item['slug']],
                array_merge($item, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Modalidades de terapia
        $modalities = [
            ['name' => 'Videollamada',    'slug' => 'videollamada',  'icon' => 'ti-video'],
            ['name' => 'Chat',            'slug' => 'chat',          'icon' => 'ti-message'],
            ['name' => 'Presencial',      'slug' => 'presencial',    'icon' => 'ti-building'],
            ['name' => 'Teléfono',        'slug' => 'telefono',      'icon' => 'ti-phone'],
            ['name' => 'A domicilio',     'slug' => 'domicilio',     'icon' => 'ti-home'],
        ];

        foreach ($modalities as $item) {
            DB::table('therapy_modalities')->updateOrInsert(
                ['slug' => $item['slug']],
                array_merge($item, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Poblaciones atendidas
        $populations = [
            ['name' => 'Niños (4-11 años)',      'slug' => 'ninos'],
            ['name' => 'Adolescentes (12-17)',    'slug' => 'adolescentes'],
            ['name' => 'Adultos jóvenes (18-25)', 'slug' => 'adultos-jovenes'],
            ['name' => 'Adultos',                 'slug' => 'adultos'],
            ['name' => 'Adultos mayores (60+)',   'slug' => 'adultos-mayores'],
            ['name' => 'Parejas',                 'slug' => 'parejas'],
            ['name' => 'Familias',                'slug' => 'familias'],
            ['name' => 'Grupos',                  'slug' => 'grupos'],
            ['name' => 'Comunidad LGBTQ+',        'slug' => 'lgbtq'],
        ];

        foreach ($populations as $item) {
            DB::table('target_populations')->updateOrInsert(
                ['slug' => $item['slug']],
                array_merge($item, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->command->info('Catálogos creados: enfoques, modalidades y poblaciones.');
    }
}
