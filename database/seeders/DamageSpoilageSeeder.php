<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DamageSpoilageLog;
use App\Models\Ingredients;
use App\Models\User;
use Carbon\Carbon;

class DamageSpoilageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user and restaurant
        $user = User::first();
        if (!$user || !$user->restaurantData) {
            $this->command->error('No user or restaurant found. Please create a user first.');
            return;
        }

        $restaurantId = $user->restaurantData->id;

        // Get some ingredients from the restaurant
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)->get();

        if ($ingredients->isEmpty()) {
            $this->command->error('No ingredients found. Please add ingredients first.');
            return;
        }

        // Create sample wastage logs
        $wastageData = [
            [
                'type' => 'damage',
                'quantity' => 5.0,
                'reason' => 'Dropped during handling',
                'notes' => 'Package fell and contents spilled',
                'incident_date' => Carbon::now()->subDays(2),
                'estimated_cost' => 250.00,
            ],
            [
                'type' => 'spoilage',
                'quantity' => 10.0,
                'reason' => 'Expired',
                'notes' => 'Past expiration date',
                'incident_date' => Carbon::now()->subDays(5),
                'estimated_cost' => 500.00,
            ],
            [
                'type' => 'damage',
                'quantity' => 3.0,
                'reason' => 'Torn packaging',
                'notes' => 'Damaged during delivery',
                'incident_date' => Carbon::now()->subDays(7),
                'estimated_cost' => 150.00,
            ],
            [
                'type' => 'spoilage',
                'quantity' => 8.0,
                'reason' => 'Mold growth',
                'notes' => 'Improper storage conditions',
                'incident_date' => Carbon::now()->subDays(10),
                'estimated_cost' => 400.00,
            ],
            [
                'type' => 'damage',
                'quantity' => 2.5,
                'reason' => 'Equipment malfunction',
                'notes' => 'Freezer breakdown caused spoilage',
                'incident_date' => Carbon::now()->subDays(15),
                'estimated_cost' => 125.00,
            ],
            [
                'type' => 'spoilage',
                'quantity' => 12.0,
                'reason' => 'Quality degradation',
                'notes' => 'No longer fresh enough for service',
                'incident_date' => Carbon::now()->subDays(20),
                'estimated_cost' => 600.00,
            ],
            [
                'type' => 'damage',
                'quantity' => 4.0,
                'reason' => 'Pest contamination',
                'notes' => 'Evidence of rodent activity',
                'incident_date' => Carbon::now()->subDays(25),
                'estimated_cost' => 200.00,
            ],
            [
                'type' => 'spoilage',
                'quantity' => 6.0,
                'reason' => 'Temperature abuse',
                'notes' => 'Left out too long',
                'incident_date' => Carbon::now()->subDays(28),
                'estimated_cost' => 300.00,
            ],
        ];

        foreach ($wastageData as $data) {
            // Randomly select an ingredient
            $ingredient = $ingredients->random();

            DamageSpoilageLog::create([
                'restaurant_id' => $restaurantId,
                'ingredient_id' => $ingredient->ingredient_id,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'unit' => $ingredient->base_unit,
                'reason' => $data['reason'],
                'notes' => $data['notes'],
                'incident_date' => $data['incident_date'],
                'estimated_cost' => $data['estimated_cost'],
                'user_id' => $user->id,
            ]);
        }

        $this->command->info('Successfully created ' . count($wastageData) . ' damage/spoilage log entries.');
    }
}
