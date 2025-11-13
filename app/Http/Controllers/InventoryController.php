<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Process received purchase order and update ingredient stock
     */
    public function processPurchaseOrderReceipt(Request $request): JsonResponse
    {
        $request->validate([
            'purchase_order_id' => 'required|integer|exists:purchase_orders,purchase_order_id',
        ]);

        try {
            $results = $this->inventoryService->addStockFromPurchaseOrder(
                $request->purchase_order_id
            );

            $successCount = collect($results['results'])->where('success', true)->count();
            $totalCount = count($results['results']);

            return response()->json([
                'success' => true,
                'message' => "Processed {$successCount}/{$totalCount} purchase order items from {$results['po_number']} successfully",
                'data' => $results,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process purchase order receipt: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process purchase order receipt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process dish sale and reduce ingredient stock
     */
    public function processDishSale(Request $request): JsonResponse
    {
        $request->validate([
            'dish_id' => 'required|integer|exists:dishes,dish_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $results = $this->inventoryService->subtractStockFromDishSale(
                $request->dish_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully processed sale of {$request->quantity} {$results['dish_name']}(s). Updated {$results['ingredients_processed']} ingredients.",
                'data' => $results,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process dish sale: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process dish sale',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check stock availability for a dish
     */
    public function checkStockAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'dish_id' => 'required|integer|exists:dishes,dish_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $availability = $this->inventoryService->checkStockAvailability(
                $request->dish_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'data' => $availability,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to check stock availability: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check stock availability',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get ingredients with low stock levels
     */
    public function getLowStockIngredients(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $restaurantId = $user->restaurantData ? $user->restaurantData->id : null;

            if (! $restaurantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Restaurant not found for the authenticated user',
                ], 400);
            }

            $lowStockIngredients = $this->inventoryService->getLowStockIngredients($restaurantId);

            return response()->json([
                'success' => true,
                'data' => $lowStockIngredients->map(function ($ingredient) use ($restaurantId) {
                    // Filter suppliers to only show those belonging to this restaurant
                    $suppliersData = $ingredient->suppliers->where('restaurant_id', $restaurantId);

                    return [
                        'ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_name' => $ingredient->ingredient_name,
                        'current_stock' => $ingredient->current_stock,
                        'packages' => (float) $ingredient->packages,
                        'reorder_level' => $ingredient->reorder_level,
                        'base_unit' => $ingredient->base_unit,
                        'suppliers' => $suppliersData->map(function ($supplier) {
                            return [
                                'supplier_id' => $supplier->supplier_id,
                                'name' => $supplier->supplier_name,
                                'package_unit' => $supplier->pivot->package_unit,
                                'package_quantity' => $supplier->pivot->package_quantity,
                                'package_contents_quantity' => $supplier->pivot->package_contents_quantity,
                                'package_contents_unit' => $supplier->pivot->package_contents_unit,
                                'package_price' => $supplier->pivot->package_price,
                                'lead_time_days' => $supplier->pivot->lead_time_days,
                            ];
                        }),
                    ];
                }),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get low stock ingredients: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get low stock ingredients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate ingredient cost for a dish
     */
    public function calculateIngredientCost(Request $request): JsonResponse
    {
        $request->validate([
            'dish_id' => 'required|integer|exists:dishes,dish_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $costAnalysis = $this->inventoryService->calculateIngredientCost(
                $request->dish_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'data' => $costAnalysis,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to calculate ingredient cost: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate ingredient cost',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch process multiple dish sales (useful for processing complete orders)
     */
    public function processBatchDishSales(Request $request): JsonResponse
    {
        $request->validate([
            'dishes' => 'required|array',
            'dishes.*.dish_id' => 'required|integer|exists:dishes,dish_id',
            'dishes.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $allResults = [];
            $errors = [];

            foreach ($request->dishes as $dishOrder) {
                try {
                    $result = $this->inventoryService->subtractStockFromDishSale(
                        $dishOrder['dish_id'],
                        $dishOrder['quantity']
                    );
                    $allResults[] = $result;

                } catch (Exception $e) {
                    $errors[] = [
                        'dish_id' => $dishOrder['dish_id'],
                        'quantity' => $dishOrder['quantity'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $response = [
                'success' => empty($errors),
                'processed_dishes' => count($allResults),
                'failed_dishes' => count($errors),
                'results' => $allResults,
            ];

            if (! empty($errors)) {
                $response['errors'] = $errors;
            }

            return response()->json($response, empty($errors) ? 200 : 207); // 207 Multi-Status for partial success

        } catch (Exception $e) {
            Log::error('Failed to process batch dish sales: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process batch dish sales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all ingredients inventory for display
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
             $user = auth()->user();
            $restaurantId = $user->restaurantData ? $user->restaurantData->id : null;

            if (!$restaurantId) {
                return back()->withErrors('Restaurant not found for the authenticated user');
            }

            // Build the query base - filter by ingredient's restaurant_id
            $query = \App\Models\Ingredients::with(['suppliers' => function ($query) use ($restaurantId) {
                    $query->where('restaurant_id', $restaurantId)
                        ->wherePivot('is_active', true);
                }])
                ->where('restaurant_id', $restaurantId); // Filter by ingredient's restaurant

            // 🔍 Apply search filter (if provided)
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('ingredient_name', 'like', "%{$search}%");
            }

            // 📦 Apply stock status filter (if provided)
            if ($request->filled('status') && $request->status !== 'all') {
                if ($request->status === 'low') {
                    $query->whereColumn('current_stock', '<=', 'reorder_level');
                } elseif ($request->status === 'in') {
                    $query->whereColumn('current_stock', '>', 'reorder_level');
                }
            }

            // Get all filtered ingredients
            $ingredients = $query->orderBy('ingredient_name')->get()
                ->map(function ($ingredient) {
                    $primarySupplier = $ingredient->suppliers->first();
                    $isLowStock = $ingredient->current_stock <= $ingredient->reorder_level;

                    return [
                        'ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_name' => $ingredient->ingredient_name,
                        'current_stock' => (float) $ingredient->current_stock,
                        'packages' => (float) $ingredient->packages,
                        'reorder_level' => (float) $ingredient->reorder_level,
                        'base_unit' => $ingredient->base_unit,
                        'supplier_name' => $primarySupplier ? $primarySupplier->supplier_name : 'No supplier',
                        'is_low_stock' => $isLowStock,
                    ];
                });

            return inertia('Inventory/Ingredients', [
                'ingredients' => $ingredients,
                'filters' => [
                    'search' => $request->search ?? '',
                    'status' => $request->status ?? 'all',
                ],
                'stats' => [
                    'total_ingredients' => $ingredients->count(),
                    'low_stock_count' => $ingredients->where('is_low_stock', true)->count(),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get ingredients inventory: ' . $e->getMessage());
            return back()->withErrors('Failed to load ingredients inventory');
        }
    }


    /**
     * Update ingredient reorder level
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateIngredient(Request $request, $ingredientId)
    {
        try {
            $request->validate([
                'reorder_level' => 'required|numeric|min:0',
                'base_unit' => 'required|string|max:20',
            ]);

            $ingredient = \App\Models\Ingredients::findOrFail($ingredientId);

            // Check if ingredient belongs to user's restaurant
            $user = auth()->user();
            $restaurantId = $user->restaurantData ? $user->restaurantData->id : null;

            // Restaurant authorization check
            // For now, we'll allow editing if user has no restaurant_id (likely admin/development)
            // or if restaurant IDs match
            if ($restaurantId && $ingredient->restaurant_id !== $restaurantId) {
                Log::warning('Unauthorized ingredient update attempt', [
                    'user_restaurant_id' => $restaurantId,
                    'ingredient_restaurant_id' => $ingredient->restaurant_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                ]);

                return back()->withErrors('You can only edit ingredients from your restaurant.');
            }

            $oldReorderLevel = $ingredient->reorder_level;
            $oldBaseUnit = $ingredient->base_unit;

            $ingredient->reorder_level = $request->reorder_level;
            $ingredient->base_unit = $request->base_unit;
            $ingredient->save();

            $message = "'{$ingredient->ingredient_name}' updated successfully";
            if ($oldReorderLevel != $ingredient->reorder_level) {
                $message .= " - Reorder level: {$oldReorderLevel} → {$ingredient->reorder_level}";
            }
            if ($oldBaseUnit != $ingredient->base_unit) {
                $message .= " - Unit: {$oldBaseUnit} → {$ingredient->base_unit}";
            }

            return back()->with('success', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for ingredient update', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return back()->withErrors($e->errors());
        } catch (Exception $e) {
            Log::error('Failed to update ingredient', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ingredient_id' => $ingredientId,
                'request_data' => $request->all(),
            ]);

            return back()->withErrors('Failed to update ingredient reorder level: '.$e->getMessage());
        }
    }
}