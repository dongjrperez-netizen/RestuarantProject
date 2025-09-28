<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ingredients')->insert([
            [
                'RestaurantID' => 1, // assuming you have at least 1 restaurant
                'IngredientName' => 'Chicken Breast',
                'BaseUnit' => 'pcs',
                'CurrentStock' => 0,
                'ReorderLevel' => 10,
            ],
            [
                'RestaurantID' => 1,
                'IngredientName' => 'Rice',
                'BaseUnit' => 'grams',
                'CurrentStock' => 0,
                'ReorderLevel' => 5000,
            ],
            [
                'RestaurantID' => 1,
                'IngredientName' => 'Cooking Oil',
                'BaseUnit' => 'ml',
                'CurrentStock' => 0,
                'ReorderLevel' => 2000,
            ],
        ]);
    }
}
