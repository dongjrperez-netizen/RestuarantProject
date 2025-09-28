<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuCategory;
use App\Models\Restaurant_Data;

class MenuCategorySeeder extends Seeder
{
    public function run()
    {
        $restaurants = Restaurant_Data::all();

        foreach ($restaurants as $restaurant) {
            $categories = [
                ['category_name' => 'Main Dishes', 'description' => 'Primary dishes and entrees', 'sort_order' => 1],
                ['category_name' => 'Appetizers', 'description' => 'Starters and small plates', 'sort_order' => 2],
                ['category_name' => 'Desserts', 'description' => 'Sweet endings', 'sort_order' => 3],
                ['category_name' => 'Beverages', 'description' => 'Drinks and beverages', 'sort_order' => 4],
            ];

            foreach ($categories as $category) {
                MenuCategory::firstOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'category_name' => $category['category_name'],
                    ],
                    [
                        'description' => $category['description'],
                        'sort_order' => $category['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}