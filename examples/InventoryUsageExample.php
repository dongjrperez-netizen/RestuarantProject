<?php

/**
 * EXAMPLE USAGE OF INVENTORY SERVICE
 *
 * This file demonstrates how to use the InventoryService in your application.
 * These are example implementations you can use in controllers, jobs, or other services.
 */

namespace App\Examples;

use App\Models\Dish;
use App\Models\PurchaseOrder;
use App\Services\InventoryService;
use Exception;
use Illuminate\Support\Facades\Log;

class InventoryUsageExample
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Example 1: Processing a received purchase order
     */
    public function exampleProcessPurchaseOrder()
    {
        try {
            // Scenario: Restaurant received a purchase order with ID 1
            $purchaseOrderId = 1;

            Log::info("Processing purchase order receipt for PO ID: {$purchaseOrderId}");

            $results = $this->inventoryService->addStockFromPurchaseOrder($purchaseOrderId);

            foreach ($results as $result) {
                if ($result['success']) {
                    Log::info("✅ Updated stock for {$result['ingredient_name']}: Added {$result['base_units_added']} {$result['old_stock']} -> {$result['new_stock']}");
                } else {
                    Log::warning("❌ Failed to update stock: {$result['message']}");
                }
            }

            return $results;

        } catch (Exception $e) {
            Log::error('Purchase order processing failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 2: Processing dish sales
     */
    public function exampleProcessDishSale()
    {
        try {
            // Scenario: Customer ordered 2 servings of Adobo (dish_id = 1)
            $dishId = 1;
            $quantity = 2;

            Log::info("Processing dish sale: {$quantity} servings of dish ID {$dishId}");

            $results = $this->inventoryService->subtractStockFromDishSale($dishId, $quantity);

            Log::info("✅ Successfully processed sale of {$results['quantity_sold']} {$results['dish_name']}(s)");

            foreach ($results['ingredients_updated'] as $ingredient) {
                if ($ingredient['success']) {
                    Log::info("  - {$ingredient['ingredient_name']}: Used {$ingredient['total_quantity_used']}, Stock: {$ingredient['old_stock']} -> {$ingredient['new_stock']}");
                } else {
                    Log::warning("  - ❌ Failed to update {$ingredient['ingredient_name']}: {$ingredient['message']}");
                }
            }

            return $results;

        } catch (Exception $e) {
            Log::error('Dish sale processing failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 3: Checking stock availability before processing order
     */
    public function exampleCheckStockBeforeOrder()
    {
        try {
            // Scenario: Check if we can fulfill an order for 5 servings of Adobo
            $dishId = 1;
            $quantity = 5;

            Log::info("Checking stock availability for {$quantity} servings of dish ID {$dishId}");

            $availability = $this->inventoryService->checkStockAvailability($dishId, $quantity);

            if ($availability['can_fulfill']) {
                Log::info("✅ Can fulfill order for {$availability['quantity_requested']} {$availability['dish_name']}(s)");

                // Proceed with order processing
                $this->exampleProcessDishSale();

            } else {
                Log::warning("❌ Cannot fulfill order for {$availability['quantity_requested']} {$availability['dish_name']}(s)");

                foreach ($availability['ingredients'] as $ingredient) {
                    if (! $ingredient['is_available']) {
                        Log::warning("  - {$ingredient['ingredient_name']}: Need {$ingredient['required_quantity']} {$ingredient['base_unit']}, have {$ingredient['current_stock']}, shortage: {$ingredient['shortage']}");
                    }
                }
            }

            return $availability;

        } catch (Exception $e) {
            Log::error('Stock availability check failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 4: Daily low stock monitoring
     */
    public function exampleLowStockMonitoring()
    {
        try {
            Log::info('Running daily low stock monitoring...');

            $restaurantId = 1; // Optional: filter by restaurant

            $lowStockIngredients = $this->inventoryService->getLowStockIngredients($restaurantId);

            if ($lowStockIngredients->isEmpty()) {
                Log::info('✅ All ingredients are above reorder level');

                return [];
            }

            Log::warning("⚠️ Found {$lowStockIngredients->count()} ingredients at or below reorder level:");

            $alertData = [];
            foreach ($lowStockIngredients as $ingredient) {
                $alertData[] = [
                    'ingredient_name' => $ingredient->ingredient_name,
                    'current_stock' => $ingredient->current_stock,
                    'reorder_level' => $ingredient->reorder_level,
                    'base_unit' => $ingredient->base_unit,
                    'suppliers_count' => $ingredient->suppliers->count(),
                ];

                Log::warning("  - {$ingredient->ingredient_name}: {$ingredient->current_stock} {$ingredient->base_unit} (reorder at {$ingredient->reorder_level})");
            }

            // You could send notifications, create purchase orders, etc.
            $this->sendLowStockNotification($alertData);

            return $alertData;

        } catch (Exception $e) {
            Log::error('Low stock monitoring failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 5: Cost analysis for menu pricing
     */
    public function exampleCostAnalysis()
    {
        try {
            // Scenario: Calculate ingredient cost for 100 servings of Adobo to determine pricing
            $dishId = 1;
            $quantity = 100;

            Log::info("Calculating ingredient cost for {$quantity} servings of dish ID {$dishId}");

            $costAnalysis = $this->inventoryService->calculateIngredientCost($dishId, $quantity);

            $costPerServing = $costAnalysis['total_ingredient_cost'] / $quantity;

            Log::info("💰 Cost analysis for {$costAnalysis['dish_name']}:");
            Log::info("  - Total ingredient cost for {$quantity} servings: ₱{$costAnalysis['total_ingredient_cost']}");
            Log::info("  - Cost per serving: ₱{$costPerServing}");
            Log::info('  - Suggested selling price (3x markup): ₱'.number_format($costPerServing * 3, 2));

            foreach ($costAnalysis['ingredients'] as $ingredient) {
                Log::info("  - {$ingredient['ingredient_name']}: {$ingredient['quantity_needed']} {$ingredient['base_unit']} @ ₱{$ingredient['cost_per_unit']}/unit = ₱{$ingredient['total_cost']}");
            }

            return $costAnalysis;

        } catch (Exception $e) {
            Log::error('Cost analysis failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Example 6: Complete restaurant order processing
     */
    public function exampleCompleteOrderProcessing()
    {
        try {
            // Scenario: Process a complete restaurant order with multiple dishes
            $orderItems = [
                ['dish_id' => 1, 'quantity' => 2], // 2 Adobo
                ['dish_id' => 2, 'quantity' => 1], // 1 Sinigang
                ['dish_id' => 3, 'quantity' => 3], // 3 Fried Rice
            ];

            Log::info('Processing complete order with '.count($orderItems).' different dishes');

            // First, check if all items are available
            $allAvailable = true;
            $availabilityChecks = [];

            foreach ($orderItems as $item) {
                $availability = $this->inventoryService->checkStockAvailability($item['dish_id'], $item['quantity']);
                $availabilityChecks[] = $availability;

                if (! $availability['can_fulfill']) {
                    $allAvailable = false;
                    Log::warning("❌ Cannot fulfill {$item['quantity']} {$availability['dish_name']}(s)");
                }
            }

            if (! $allAvailable) {
                throw new Exception('Order cannot be fulfilled due to insufficient stock');
            }

            // Process each dish sale
            $results = [];
            foreach ($orderItems as $item) {
                $result = $this->inventoryService->subtractStockFromDishSale($item['dish_id'], $item['quantity']);
                $results[] = $result;
                Log::info("✅ Processed {$result['quantity_sold']} {$result['dish_name']}(s)");
            }

            Log::info('✅ Complete order processed successfully!');

            return [
                'availability_checks' => $availabilityChecks,
                'processing_results' => $results,
            ];

        } catch (Exception $e) {
            Log::error('Complete order processing failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper method to send low stock notifications
     */
    private function sendLowStockNotification(array $lowStockData)
    {
        // Example implementation - you could integrate with email, SMS, Slack, etc.
        Log::info('📧 Sending low stock notification for '.count($lowStockData).' ingredients');

        // Example: Create in-app notification, send email to manager, etc.
        // NotificationService::send('low_stock', $lowStockData);
        // Mail::to($manager->email)->send(new LowStockAlert($lowStockData));
    }

    /**
     * Example workflow: Daily restaurant operations
     */
    public function exampleDailyOperations()
    {
        try {
            Log::info('🌅 Starting daily restaurant operations workflow...');

            // 1. Check for low stock items
            $this->exampleLowStockMonitoring();

            // 2. Process any pending purchase order receipts
            $pendingOrders = PurchaseOrder::where('status', 'received')
                ->whereNull('actual_delivery_date')
                ->get();

            foreach ($pendingOrders as $order) {
                $this->inventoryService->addStockFromPurchaseOrder($order->purchase_order_id);
                Log::info("✅ Processed receipt for PO #{$order->po_number}");
            }

            // 3. Run cost analysis for popular dishes
            $popularDishes = Dish::where('is_available', true)->take(5)->get();

            foreach ($popularDishes as $dish) {
                $this->inventoryService->calculateIngredientCost($dish->dish_id, 50);
            }

            Log::info('✅ Daily operations workflow completed');

        } catch (Exception $e) {
            Log::error('Daily operations workflow failed: '.$e->getMessage());
            throw $e;
        }
    }
}
