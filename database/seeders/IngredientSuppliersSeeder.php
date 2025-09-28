<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSuppliersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ingredient_suppliers')->insert([
            // Chicken Breast from Fresh Farms
            [
                'IngredientID' => 1,
                'SupplierID' => 1,
                'PackageUnit' => 'Box',
                'PackageQuantity' => 10, // 1 box = 10 pcs
                'PackagePrice' => 1200.00,
            ],
            // Rice from Golden Foods
            [
                'IngredientID' => 2,
                'SupplierID' => 2,
                'PackageUnit' => 'Sack',
                'PackageQuantity' => 25000, // 1 sack = 25kg (25000g)
                'PackagePrice' => 1800.00,
            ],
            // Cooking Oil from Golden Foods
            [
                'IngredientID' => 3,
                'SupplierID' => 2,
                'PackageUnit' => 'Box',
                'PackageQuantity' => 12000, // 1 box = 12 bottles (1000ml each)
                'PackagePrice' => 1500.00,
            ],
        ]);
    }
}
