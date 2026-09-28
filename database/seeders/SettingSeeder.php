<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_name',        'value' => 'Contigo Terapia',         'type' => 'string',  'group' => 'general', 'label' => 'Nombre del sitio',        'is_public' => true],
            ['key' => 'site_description', 'value' => 'Plataforma de telemedicina', 'type' => 'string', 'group' => 'general', 'label' => 'Descripción',            'is_public' => true],
            ['key' => 'site_logo',        'value' => null,                       'type' => 'image',   'group' => 'general', 'label' => 'Logo',                    'is_public' => true],
            ['key' => 'site_favicon',     'value' => null,                       'type' => 'image',   'group' => 'general', 'label' => 'Favicon',                 'is_public' => true],

            // Contacto
            ['key' => 'contact_phone',    'value' => null,                       'type' => 'string',  'group' => 'contact', 'label' => 'Teléfono',                'is_public' => true],
            ['key' => 'contact_email',    'value' => null,                       'type' => 'string',  'group' => 'contact', 'label' => 'Email de contacto',       'is_public' => true],
            ['key' => 'contact_whatsapp', 'value' => null,                       'type' => 'string',  'group' => 'contact', 'label' => 'WhatsApp',                'is_public' => true],
            ['key' => 'contact_address',  'value' => null,                       'type' => 'string',  'group' => 'contact', 'label' => 'Dirección',               'is_public' => true],
            ['key' => 'manager_name',     'value' => null,                       'type' => 'string',  'group' => 'contact', 'label' => 'Nombre del encargado',    'is_public' => false],
            ['key' => 'manager_phone',    'value' => null,                       'type' => 'string',  'group' => 'contact', 'label' => 'Teléfono del encargado',  'is_public' => false],
            ['key' => 'manager_email',    'value' => null,                       'type' => 'string',  'group' => 'contact', 'label' => 'Email del encargado',     'is_public' => false],

            // Redes sociales
            ['key' => 'social_instagram', 'value' => null,                       'type' => 'string',  'group' => 'social',  'label' => 'Instagram',               'is_public' => true],
            ['key' => 'social_facebook',  'value' => null,                       'type' => 'string',  'group' => 'social',  'label' => 'Facebook',                'is_public' => true],
            ['key' => 'social_tiktok',    'value' => null,                       'type' => 'string',  'group' => 'social',  'label' => 'TikTok',                  'is_public' => true],
            ['key' => 'social_twitter',   'value' => null,                       'type' => 'string',  'group' => 'social',  'label' => 'Twitter/X',               'is_public' => true],
            ['key' => 'social_youtube',   'value' => null,                       'type' => 'string',  'group' => 'social',  'label' => 'YouTube',                 'is_public' => true],

            // Legal
            ['key' => 'privacy_policy',   'value' => null,  'type' => 'text',    'group' => 'legal',       'label' => 'Política de privacidad',  'is_public' => true],
            ['key' => 'data_policy',      'value' => null,  'type' => 'text',    'group' => 'legal',       'label' => 'Política de datos',       'is_public' => true],
            ['key' => 'terms_conditions', 'value' => null,  'type' => 'text',    'group' => 'legal',       'label' => 'Términos y condiciones',  'is_public' => true],

            // Comisiones
            ['key' => 'default_commission_percentage',  'value' => '15.00', 'type' => 'decimal', 'group' => 'commissions', 'label' => 'Comisión por defecto (%)',             'is_public' => false],
            ['key' => 'stripe_fee_percentage',          'value' => '3.60',  'type' => 'decimal', 'group' => 'commissions', 'label' => 'Comisión Stripe (%)',                  'is_public' => false],
            ['key' => 'stripe_fee_fixed',               'value' => '3.00',  'type' => 'decimal', 'group' => 'commissions', 'label' => 'Comisión Stripe fija (MXN)',           'is_public' => false],
            ['key' => 'mercadopago_fee_percentage',     'value' => '3.49',  'type' => 'decimal', 'group' => 'commissions', 'label' => 'Comisión MercadoPago (%)',             'is_public' => false],
            ['key' => 'mercadopago_fee_fixed',          'value' => '0.00',  'type' => 'decimal', 'group' => 'commissions', 'label' => 'Comisión MercadoPago fija (MXN)',      'is_public' => false],

            // Membresías
            ['key' => 'membership_price_monthly',       'value' => '499.00','type' => 'decimal', 'group' => 'membership',  'label' => 'Precio membresía mensual (MXN)',       'is_public' => true],
            ['key' => 'membership_commission_discount', 'value' => '5.00',  'type' => 'decimal', 'group' => 'membership',  'label' => 'Descuento comisión con membresía (%)', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
