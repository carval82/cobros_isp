<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Pedro Capacho',
                'email' => 'pcapacho24@gmail.com',
                'password' => Hash::make('Anavalia331$'),
            ],
            [
                'name' => 'Domingo Rivero',
                'email' => 'domingorivero.iutc@gmail.com',
                'password' => Hash::make('Zeus19$$'),
            ],
        ];

        foreach ($admins as $admin) {
            User::firstOrCreate(
                ['email' => $admin['email']],
                $admin
            );
        }
    }
}
