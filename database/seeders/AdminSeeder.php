<?php

namespace Database\Seeders;

use App\Models\Administrator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Administrator::updateOrCreate(
            ['email' => 'Admin@gmail.com'], // Find by email
            [
                'password' => Hash::make('Admin123'),
                'is_active' => true,
            ]
        );
    }
}
