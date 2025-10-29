<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('restaurant_data')->insert([
            [
                'name' => 'ServeWise Demo Restaurant',
                'address' => 'Manila City',
                'contact_number' => '09170000000',
                'email' => 'demo@servewise.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
