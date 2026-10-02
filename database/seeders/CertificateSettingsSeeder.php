<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CertificateSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key'       => 'certificate_signer_name',
                'value'     => 'Dr. Director Médico',
                'type'      => 'string',
                'label'     => 'Nombre del firmante',
                'group'     => 'certificates',
                'is_public' => false,
            ],
            [
                'key'       => 'certificate_signer_title',
                'value'     => 'Director de Formación',
                'type'      => 'string',
                'label'     => 'Cargo del firmante',
                'group'     => 'certificates',
                'is_public' => false,
            ],
            [
                'key'       => 'certificate_signature_image',
                'value'     => '',
                'type'      => 'string',
                'label'     => 'Ruta de imagen de firma',
                'group'     => 'certificates',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
