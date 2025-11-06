<?php

namespace Database\Seeders;

use App\Models\Administrator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Administrator::create([
            'email' => 'Admin@gmail.com',
            'password' => Hash::make('Admin123'),
            'is_active' => true,
        ]);
    }
}
