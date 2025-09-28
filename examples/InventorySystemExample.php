<?php

namespace App\Examples;

use App\Models\Dish;
use App\Models\Ingredients;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive examples of how to use the Laravel Inventory Management System
 *
 * This shows production-ready usage patterns for:
 * 1. Processing purchase order receipts
 * 2. Handling dish sales with stock deduction
 * 3. Stock availability checking
 * 4. Low stock monitoring
 * 5. Cost calculations
 */
class InventorySystemExample
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Example 1: Processing a Purchase Order Receipt
     * When goods arrive from supplier → convert package quantities to base units
     */
    public function processPurchaseOrderExample()
    {
        try {
            // Scenario: Restaurant receives a purchase order with:
            // - 5 sacks of rice (each sack = 25,000 grams)
            // - 10 bottles of soy sauce (each bottle = 500 ml)

            $purchaseOrderId = 1; // PO with received quantities already set

            $results = $this->inventoryService->addStockFromPurchaseOrder($purchaseOrderId);

            Log::info('Purchase order processed successfully', [
                'po_number' => $results['po_number'],
                'supplier' => $results['supplier'],
                'items_processed' => $results['items_processed'],
                'total_items' => $results['total_items'],
            ]);

            // Example result:
            // {
            //     "purchase_order_id": 1,
            //     "po_number": "PO-2024-000001",
            //     "supplier": "Metro Food Supplies",
            //     "items_processed": 2,
            //     "total_items": 2,
            //     "results": [
            //         {
            //             "item_id": 1,
            //             "ingredient_id": 1,
            //             "ingredient_name": "Rice",
            //             "success": true,
            //             "packages_received": 5,
            //             "base_units_added": 125000,  // 5 sacks × 25,000g each
            //             "old_stock": 50000,
            //             "new_stock": 175000,
            //             "base_unit": "grams"
            //         },
            //         {
            //             "item_id": 2,
            //             "ingredient_id": 2,
            //             "ingredient_name": "Soy Sauce",
            //             "success": true,
            //             "packages_received": 10,
            //             "base_units_added": 5000,  // 10 bottles × 500ml each
            //             "old_stock": 2000,
            //             "new_stock": 7000,
            //             "base_unit": "ml"
            //         }
            //     ]
            // }

            return $results;

        } catch (\Exception $e) {
            Log::error('Purchase order processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Example 2: Processing Dish Sales
     * When a dish is sold → deduct ingredients based on recipe
     */
    public function processDishSaleExample()
    {
        try {
            // Scenario: Customer orders 3 servings of "Chicken Adobo"
            // Recipe requires per serving:
            // - 200g chicken
            // - 50g garlic
            // - 20ml soy sauce
            // - 10ml vinegar

            $dishId = 1; // Chicken Adobo
            $quantitySold = 3;

            // First, check if we have enough stock
            $availability = $this->inventoryService->checkStockAvailability($dishId, $quantitySold);

            if (! $availability['can_fulfill']) {
                Log::warning('Insufficient stock for dish sale', [
                    'dish_name' => $availability['dish_name'],
                    'quantity_requested' => $quantitySold,
                    'shortages' => collect($availability['ingredients'])
                        ->where('is_available', false)
                        ->values(),
                ]);

                return ['error' => 'Insufficient stock'];
            }

            // Process the sale
            $results = $this->inventoryService->subtractStockFromDishSale($dishId, $quantitySold);

            Log::info('Dish sale processed successfully', [
                'dish_name' => $results['dish_name'],
                'quantity_sold' => $results['quantity_sold'],
                'ingredients_processed' => $results['ingredients_processed'],
            ]);

            // Example result:
            // {
            //     "dish_id": 1,
            //     "dish_name": "Chicken Adobo",
            //     "quantity_sold": 3,
            //     "ingredients_processed": 4,
            //     "ingredients_updated": [
            //         {
            //             "ingredient_id": 1,
            //             "ingredient_name": "Chicken",
            //             "success": true,
            //             "quantity_per_dish": 200,
            //             "total_quantity_used": 600,  // 200g × 3 servings
            //             "old_stock": 5000,
            //             "new_stock": 4400,
            //             "base_unit": "grams"
            //         },
            //         // ... other ingredients
            //     ]
            // }

            return $results;

        } catch (\Exception $e) {
            Log::error('Dish sale processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Example 3: Batch Processing Multiple Dishes (Restaurant Order)
     */
    public function processBatchSalesExample()
    {
        try {
            // Scenario: Process a complete restaurant order with multiple dishes
            $orderItems = [
                ['dish_id' => 1, 'quantity' => 2], // 2x Chicken Adobo
                ['dish_id' => 2, 'quantity' => 1], // 1x Beef Stew
                ['dish_id' => 3, 'quantity' => 3], // 3x Fried Rice
            ];

            $allResults = [];
            $errors = [];

            foreach ($orderItems as $item) {
                try {
                    $result = $this->inventoryService->subtractStockFromDishSale(
                        $item['dish_id'],
                        $item['quantity']
                    );
                    $allResults[] = $result;
                } catch (\Exception $e) {
                    $errors[] = [
                        'dish_id' => $item['dish_id'],
                        'quantity' => $item['quantity'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('Batch dish sales processed', [
                'successful_dishes' => count($allResults),
                'failed_dishes' => count($errors),
            ]);

            return [
                'successful_dishes' => count($allResults),
                'failed_dishes' => count($errors),
                'results' => $allResults,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            Log::error('Batch sales processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Example 4: Stock Monitoring and Alerts
     */
    public function stockMonitoringExample()
    {
        try {
            $restaurantId = 1;

            // Get all low stock ingredients
            $lowStockIngredients = $this->inventoryService->getLowStockIngredients($restaurantId);

            foreach ($lowStockIngredients as $ingredient) {
                if ($ingredient->current_stock <= 0) {
                    // Critical: Out of stock
                    Log::critical('Ingredient out of stock', [
                        'ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_name' => $ingredient->ingredient_name,
                        'restaurant_id' => $restaurantId,
                    ]);

                    // Trigger urgent reorder notification
                    $this->sendUrgentReorderAlert($ingredient);

                } elseif ($ingredient->current_stock <= $ingredient->reorder_level) {
                    // Warning: Low stock
                    Log::warning('Ingredient low stock', [
                        'ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_name' => $ingredient->ingredient_name,
                        'current_stock' => $ingredient->current_stock,
                        'reorder_level' => $ingredient->reorder_level,
                        'restaurant_id' => $restaurantId,
                    ]);

                    // Suggest reorder
                    $this->sendReorderReminder($ingredient);
                }
            }

            return $lowStockIngredients;

        } catch (\Exception $e) {
            Log::error('Stock monitoring failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Example 5: Cost Analysis for Menu Planning
     */
    public function costAnalysisExample()
    {
        try {
            // Calculate ingredient costs for different dish quantities
            $dishes = [
                ['dish_id' => 1, 'name' => 'Chicken Adobo'],
                ['dish_id' => 2, 'name' => 'Beef Stew'],
                ['dish_id' => 3, 'name' => 'Fried Rice'],
            ];

            $quantities = [1, 10, 50]; // Single serving, small batch, large batch

            $costAnalysis = [];

            foreach ($dishes as $dish) {
                foreach ($quantities as $quantity) {
                    $cost = $this->inventoryService->calculateIngredientCost($dish['dish_id'], $quantity);

                    $costAnalysis[] = [
                        'dish_name' => $dish['name'],
                        'quantity' => $quantity,
                        'total_ingredient_cost' => $cost['total_ingredient_cost'],
                        'cost_per_serving' => $cost['total_ingredient_cost'] / $quantity,
                    ];
                }
            }

            // Log cost analysis for menu pricing decisions
            Log::info('Cost analysis completed', ['analysis' => $costAnalysis]);

            return $costAnalysis;

        } catch (\Exception $e) {
            Log::error('Cost analysis failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Example 6: Real-time Stock Validation Before Order Processing
     */
    public function validateOrderBeforeProcessing($orderItems)
    {
        try {
            $validation = [];
            $canFulfillOrder = true;

            foreach ($orderItems as $item) {
                $availability = $this->inventoryService->checkStockAvailability(
                    $item['dish_id'],
                    $item['quantity']
                );

                $validation[] = $availability;

                if (! $availability['can_fulfill']) {
                    $canFulfillOrder = false;
                }
            }

            if (! $canFulfillOrder) {
                Log::warning('Order cannot be fulfilled due to insufficient stock', [
                    'order_items' => $orderItems,
                    'validation_results' => $validation,
                ]);
            }

            return [
                'can_fulfill_order' => $canFulfillOrder,
                'dish_validations' => $validation,
            ];

        } catch (\Exception $e) {
            Log::error('Order validation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Helper methods for notifications
     */
    private function sendUrgentReorderAlert(Ingredients $ingredient)
    {
        // Implementation would send email/SMS/notification to procurement team
        Log::info('Urgent reorder alert sent', [
            'ingredient' => $ingredient->ingredient_name,
            'current_stock' => $ingredient->current_stock,
        ]);
    }

    private function sendReorderReminder(Ingredients $ingredient)
    {
        // Implementation would send reorder reminder
        Log::info('Reorder reminder sent', [
            'ingredient' => $ingredient->ingredient_name,
            'current_stock' => $ingredient->current_stock,
            'reorder_level' => $ingredient->reorder_level,
        ]);
    }
}

/**
 * EXAMPLE CONTROLLER USAGE
 *
 * Below are examples of how to use the InventoryController endpoints:
 */

/*
// 1. Process Purchase Order Receipt
POST /inventory/purchase-order/receipt
Content-Type: application/json

{
    "purchase_order_id": 1
}

// Response:
{
    "success": true,
    "message": "Processed 2/2 purchase order items from PO-2024-000001 successfully",
    "data": {
        "purchase_order_id": 1,
        "po_number": "PO-2024-000001",
        "supplier": "Metro Food Supplies",
        "items_processed": 2,
        "total_items": 2,
        "results": [...]
    }
}

// 2. Process Dish Sale
POST /inventory/dish/sale
Content-Type: application/json

{
    "dish_id": 1,
    "quantity": 3
}

// Response:
{
    "success": true,
    "message": "Successfully processed sale of 3 Chicken Adobo(s). Updated 4 ingredients.",
    "data": {
        "dish_id": 1,
        "dish_name": "Chicken Adobo",
        "quantity_sold": 3,
        "ingredients_processed": 4,
        "ingredients_updated": [...]
    }
}

// 3. Check Stock Availability
GET /inventory/dish/stock-check?dish_id=1&quantity=5

// Response:
{
    "success": true,
    "data": {
        "dish_id": 1,
        "dish_name": "Chicken Adobo",
        "quantity_requested": 5,
        "can_fulfill": true,
        "ingredients": [
            {
                "ingredient_id": 1,
                "ingredient_name": "Chicken",
                "base_unit": "grams",
                "required_quantity": 1000,
                "current_stock": 5000,
                "is_available": true,
                "shortage": 0
            }
        ]
    }
}

// 4. Get Low Stock Ingredients
GET /inventory/ingredients/low-stock?restaurant_id=1

// Response:
{
    "success": true,
    "data": [
        {
            "ingredient_id": 1,
            "ingredient_name": "Garlic",
            "current_stock": 100,
            "reorder_level": 500,
            "base_unit": "grams",
            "suppliers": [...]
        }
    ]
}

// 5. Calculate Ingredient Cost
GET /inventory/dish/ingredient-cost?dish_id=1&quantity=10

// Response:
{
    "success": true,
    "data": {
        "dish_id": 1,
        "dish_name": "Chicken Adobo",
        "quantity": 10,
        "total_ingredient_cost": 45.50,
        "ingredients": [...]
    }
}

// 6. Batch Process Multiple Dishes
POST /inventory/dish/batch-sale
Content-Type: application/json

{
    "dishes": [
        {"dish_id": 1, "quantity": 2},
        {"dish_id": 2, "quantity": 1},
        {"dish_id": 3, "quantity": 3}
    ]
}

// Response:
{
    "success": true,
    "processed_dishes": 3,
    "failed_dishes": 0,
    "results": [...]
}
*/
