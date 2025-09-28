<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\DishIngredient;
use App\Models\Ingredients;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Restaurant_Data;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventorySystemSeeder extends Seeder
{
    public function run(): void
    {
        // Get first restaurant or create one
        $restaurant = Restaurant_Data::first();
        if (! $restaurant) {
            $user = User::first();
            if (! $user) {
                $user = User::create([
                    'first_name' => 'Test',
                    'last_name' => 'Owner',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'male',
                    'email' => 'owner@restaurant.test',
                    'phonenumber' => '+1234567890',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'role_id' => 1,
                    'status' => 'active',
                ]);
            }

            $restaurant = Restaurant_Data::create([
                'user_id' => $user->id,
                'restaurant_name' => 'Sample Restaurant',
                'contact_number' => '+1234567890',
                'address' => '123 Main St, City, Country',
                'postal_code' => '12345',
            ]);
        }

        // Create suppliers
        $suppliers = [
            [
                'name' => 'Metro Food Supplies',
                'contact_person' => 'John Supplier',
                'email' => 'john@metrofood.com',
                'phone' => '+1234567801',
                'address' => '456 Supply St, City',
                'payment_terms' => 'Net 30',
                'lead_time_days' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Fresh Ingredients Co.',
                'contact_person' => 'Maria Santos',
                'email' => 'maria@freshingredients.com',
                'phone' => '+1234567802',
                'address' => '789 Fresh Ave, City',
                'payment_terms' => 'Net 15',
                'lead_time_days' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::create($supplierData);
        }

        $supplier1 = Supplier::where('name', 'Metro Food Supplies')->first();
        $supplier2 = Supplier::where('name', 'Fresh Ingredients Co.')->first();

        // Create ingredients with current stock
        $ingredients = [
            [
                'restaurant_id' => $restaurant->id,
                'ingredient_name' => 'Rice',
                'base_unit' => 'grams',
                'current_stock' => 50000, // 50kg
                'reorder_level' => 10000, // 10kg
            ],
            [
                'restaurant_id' => $restaurant->id,
                'ingredient_name' => 'Chicken Breast',
                'base_unit' => 'grams',
                'current_stock' => 5000, // 5kg
                'reorder_level' => 2000, // 2kg
            ],
            [
                'restaurant_id' => $restaurant->id,
                'ingredient_name' => 'Soy Sauce',
                'base_unit' => 'ml',
                'current_stock' => 2000, // 2 liters
                'reorder_level' => 500, // 500ml
            ],
            [
                'restaurant_id' => $restaurant->id,
                'ingredient_name' => 'Garlic',
                'base_unit' => 'grams',
                'current_stock' => 100, // 100g (low stock for testing)
                'reorder_level' => 500, // 500g
            ],
            [
                'restaurant_id' => $restaurant->id,
                'ingredient_name' => 'Vinegar',
                'base_unit' => 'ml',
                'current_stock' => 1000, // 1 liter
                'reorder_level' => 250, // 250ml
            ],
            [
                'restaurant_id' => $restaurant->id,
                'ingredient_name' => 'Onions',
                'base_unit' => 'grams',
                'current_stock' => 3000, // 3kg
                'reorder_level' => 1000, // 1kg
            ],
        ];

        foreach ($ingredients as $ingredientData) {
            Ingredients::create($ingredientData);
        }

        // Create ingredient-supplier relationships (pivot table)
        $rice = Ingredients::where('ingredient_name', 'Rice')->first();
        $chicken = Ingredients::where('ingredient_name', 'Chicken Breast')->first();
        $soySauce = Ingredients::where('ingredient_name', 'Soy Sauce')->first();
        $garlic = Ingredients::where('ingredient_name', 'Garlic')->first();
        $vinegar = Ingredients::where('ingredient_name', 'Vinegar')->first();
        $onions = Ingredients::where('ingredient_name', 'Onions')->first();

        // Supplier 1 relationships
        $rice->suppliers()->attach($supplier1->supplier_id, [
            'package_unit' => 'sack',
            'package_quantity' => 25000, // 25kg per sack
            'package_price' => 1200.00,
            'lead_time_days' => 3,
            'minimum_order_quantity' => 5,
            'is_active' => true,
        ]);

        $chicken->suppliers()->attach($supplier1->supplier_id, [
            'package_unit' => 'tray',
            'package_quantity' => 1000, // 1kg per tray
            'package_price' => 250.00,
            'lead_time_days' => 1,
            'minimum_order_quantity' => 10,
            'is_active' => true,
        ]);

        $soySauce->suppliers()->attach($supplier2->supplier_id, [
            'package_unit' => 'bottle',
            'package_quantity' => 500, // 500ml per bottle
            'package_price' => 45.00,
            'lead_time_days' => 1,
            'minimum_order_quantity' => 12,
            'is_active' => true,
        ]);

        $garlic->suppliers()->attach($supplier2->supplier_id, [
            'package_unit' => 'kg',
            'package_quantity' => 1000, // 1kg per package
            'package_price' => 120.00,
            'lead_time_days' => 1,
            'minimum_order_quantity' => 5,
            'is_active' => true,
        ]);

        $vinegar->suppliers()->attach($supplier2->supplier_id, [
            'package_unit' => 'bottle',
            'package_quantity' => 350, // 350ml per bottle
            'package_price' => 25.00,
            'lead_time_days' => 1,
            'minimum_order_quantity' => 12,
            'is_active' => true,
        ]);

        $onions->suppliers()->attach($supplier1->supplier_id, [
            'package_unit' => 'kg',
            'package_quantity' => 1000, // 1kg per package
            'package_price' => 80.00,
            'lead_time_days' => 2,
            'minimum_order_quantity' => 10,
            'is_active' => true,
        ]);

        // Create sample dishes
        $dishes = [
            [
                'restaurant_id' => $restaurant->id,
                'dish_name' => 'Chicken Adobo',
                'description' => 'Traditional Filipino braised chicken in soy sauce and vinegar',
                'price' => 180.00,
                'category' => 'Main Course',
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurant->id,
                'dish_name' => 'Garlic Fried Rice',
                'description' => 'Filipino-style fried rice with garlic',
                'price' => 120.00,
                'category' => 'Rice',
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurant->id,
                'dish_name' => 'Chicken Teriyaki',
                'description' => 'Grilled chicken with teriyaki glaze',
                'price' => 200.00,
                'category' => 'Main Course',
                'is_available' => true,
            ],
        ];

        foreach ($dishes as $dishData) {
            Dish::create($dishData);
        }

        // Create dish ingredients (recipes)
        $adobo = Dish::where('dish_name', 'Chicken Adobo')->first();
        $friedRice = Dish::where('dish_name', 'Garlic Fried Rice')->first();
        $teriyaki = Dish::where('dish_name', 'Chicken Teriyaki')->first();

        // Chicken Adobo recipe (serves 1)
        DishIngredient::create([
            'dish_id' => $adobo->dish_id,
            'ingredient_id' => $chicken->ingredient_id,
            'quantity_needed' => 200, // 200g chicken
            'unit_of_measure' => 'grams',
        ]);

        DishIngredient::create([
            'dish_id' => $adobo->dish_id,
            'ingredient_id' => $soySauce->ingredient_id,
            'quantity_needed' => 20, // 20ml soy sauce
            'unit_of_measure' => 'ml',
        ]);

        DishIngredient::create([
            'dish_id' => $adobo->dish_id,
            'ingredient_id' => $vinegar->ingredient_id,
            'quantity_needed' => 10, // 10ml vinegar
            'unit_of_measure' => 'ml',
        ]);

        DishIngredient::create([
            'dish_id' => $adobo->dish_id,
            'ingredient_id' => $garlic->ingredient_id,
            'quantity_needed' => 10, // 10g garlic
            'unit_of_measure' => 'grams',
        ]);

        DishIngredient::create([
            'dish_id' => $adobo->dish_id,
            'ingredient_id' => $onions->ingredient_id,
            'quantity_needed' => 50, // 50g onions
            'unit_of_measure' => 'grams',
        ]);

        // Garlic Fried Rice recipe (serves 1)
        DishIngredient::create([
            'dish_id' => $friedRice->dish_id,
            'ingredient_id' => $rice->ingredient_id,
            'quantity_needed' => 150, // 150g rice
            'unit_of_measure' => 'grams',
        ]);

        DishIngredient::create([
            'dish_id' => $friedRice->dish_id,
            'ingredient_id' => $garlic->ingredient_id,
            'quantity_needed' => 15, // 15g garlic
            'unit_of_measure' => 'grams',
        ]);

        DishIngredient::create([
            'dish_id' => $friedRice->dish_id,
            'ingredient_id' => $soySauce->ingredient_id,
            'quantity_needed' => 10, // 10ml soy sauce
            'unit_of_measure' => 'ml',
        ]);

        // Chicken Teriyaki recipe (serves 1)
        DishIngredient::create([
            'dish_id' => $teriyaki->dish_id,
            'ingredient_id' => $chicken->ingredient_id,
            'quantity_needed' => 250, // 250g chicken
            'unit_of_measure' => 'grams',
        ]);

        DishIngredient::create([
            'dish_id' => $teriyaki->dish_id,
            'ingredient_id' => $soySauce->ingredient_id,
            'quantity_needed' => 15, // 15ml soy sauce
            'unit_of_measure' => 'ml',
        ]);

        // Create a sample purchase order
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-2025-000001',
            'restaurant_id' => $restaurant->id,
            'supplier_id' => $supplier1->supplier_id,
            'status' => 'received',
            'order_date' => now()->subDays(3),
            'expected_delivery_date' => now()->subDays(1),
            'actual_delivery_date' => now(),
            'subtotal' => 8500.00,
            'tax_amount' => 1020.00,
            'total_amount' => 9520.00,
            'notes' => 'Sample purchase order for testing inventory system',
            'received_by' => 'Store Manager',
            'receiving_notes' => 'All items received in good condition',
        ]);

        // Create purchase order items
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'ingredient_id' => $rice->ingredient_id,
            'ordered_quantity' => 5, // 5 sacks
            'received_quantity' => 5, // All received
            'unit_price' => 1200.00,
            'total_price' => 6000.00,
            'unit_of_measure' => 'sacks',
            'notes' => 'Premium jasmine rice',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'ingredient_id' => $chicken->ingredient_id,
            'ordered_quantity' => 10, // 10 trays
            'received_quantity' => 10, // All received
            'unit_price' => 250.00,
            'total_price' => 2500.00,
            'unit_of_measure' => 'trays',
            'notes' => 'Fresh chicken breast',
        ]);

        echo "✅ Inventory system sample data created successfully!\n";
        echo "📊 Created:\n";
        echo "   - 2 Suppliers\n";
        echo "   - 6 Ingredients\n";
        echo "   - 3 Dishes with recipes\n";
        echo "   - 1 Purchase Order with 2 items\n";
        echo "   - All ingredient-supplier relationships\n";
        echo "\n🧪 Ready for testing!\n";
    }
}
