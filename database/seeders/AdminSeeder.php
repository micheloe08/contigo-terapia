<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@contigo-terapia.com'],
            [
                'name'      => 'Administrador',
                'email'     => 'admin@contigo-terapia.com',
                'password'  => Hash::make('Admin2026!'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        $this->command->info('Admin creado: admin@contigo-terapia.com / Admin2026!');
    }
}
