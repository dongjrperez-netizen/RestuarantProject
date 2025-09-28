<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('suppliers')->insert([
            [
                'restaurant_id' => 1,
                'supplier_name' => 'Fresh Farms',
                'contact_number' => '09180000001',
                'address' => 'Quezon City',
                'email' => 'contact@freshfarms.com',
                'password' => bcrypt('password'),
                'credit_limit' => 10000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => 1,
                'supplier_name' => 'Golden Foods Distributor',
                'contact_number' => '09180000002',
                'address' => 'Makati',
                'email' => 'info@goldenfoods.com',
                'password' => bcrypt('password'),
                'credit_limit' => 15000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
