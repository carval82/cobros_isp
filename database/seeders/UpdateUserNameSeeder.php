<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUserNameSeeder extends Seeder
{
    public function run(): void
    {
        User::where('email', 'pcapacho24@gmail.com')
            ->update(['name' => 'Luis Carlos Correa Arrieta']);
    }
}
