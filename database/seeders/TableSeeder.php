<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user or create one for testing
        $user = User::first();

        if (!$user) {
            return; // Skip if no users exist
        }

        $tables = [
            [
                'table_number' => 'T1',
                'table_name' => 'Window Table 1',
                'seats' => 4,
                'status' => 'available',
                'description' => 'Nice window view table for couples or small families',
                'x_position' => 10.5,
                'y_position' => 15.0,
            ],
            [
                'table_number' => 'T2',
                'table_name' => 'Window Table 2',
                'seats' => 2,
                'status' => 'occupied',
                'description' => 'Cozy table for two by the window',
                'x_position' => 10.5,
                'y_position' => 25.0,
            ],
            [
                'table_number' => 'VIP1',
                'table_name' => 'VIP Table',
                'seats' => 8,
                'status' => 'reserved',
                'description' => 'Premium table for special occasions',
                'x_position' => 30.0,
                'y_position' => 20.0,
            ],
            [
                'table_number' => 'BAR1',
                'table_name' => 'Bar Counter 1',
                'seats' => 6,
                'status' => 'available',
                'description' => 'High bar table with stools',
                'x_position' => 50.0,
                'y_position' => 10.0,
            ],
            [
                'table_number' => 'T3',
                'table_name' => 'Center Table',
                'seats' => 6,
                'status' => 'maintenance',
                'description' => 'Currently under repair',
                'x_position' => 25.0,
                'y_position' => 35.0,
            ],
        ];

        foreach ($tables as $tableData) {
            Table::create(array_merge($tableData, ['user_id' => $user->id]));
        }
    }
}
