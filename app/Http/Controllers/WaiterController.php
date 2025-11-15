<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\TableReservation;
use App\Models\Employee;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\CustomerRequest;
use App\Models\Dish;
use App\Models\MenuPlan;
use App\Events\OrderCreated;
use App\Events\OrderServed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WaiterController extends Controller
{
    /**
     * Get ready orders for the waiter's restaurant
     */
    private function getReadyOrders($userId)
    {
        return CustomerOrder::where('restaurant_id', $userId)
            ->where('status', 'ready')
            ->with(['table'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->order_id,
                    'order_number' => $order->order_number,
                    'table_number' => $order->table->table_number,
                    'table_name' => $order->table->table_name,
                    'table_id' => $order->table->id,
                ];
            });
    }

    public function dashboard()
    {
        // Get the authenticated employee
        $employee = Auth::guard('waiter')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        // Get the restaurant ID through the Restaurant_Data relationship
        $restaurantData = \App\Models\Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (!$restaurantData) {
            abort(404, 'Restaurant data not found for this employee.');
        }
        $restaurantId = $restaurantData->id;

        // Get tables for the restaurant this waiter belongs to
        $tables = Table::where('user_id', $employee->user_id)
            ->orderBy('table_number')
            ->get();

        // Get today's menu plan using the same logic as the API
        $today = now()->format('Y-m-d');

        \Log::info('Waiter Dashboard - Menu Plan Lookup', [
            'employee_user_id' => $employee->user_id,
            'restaurant_id' => $restaurantId,
            'today' => $today,
            'employee' => $employee->firstname . ' ' . $employee->lastname
        ]);

        // First, try to find a specific menu plan for today
        // Priority: weekly plans over daily plans when both cover the same date
        $activeMenuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderByRaw("CASE WHEN plan_type = 'weekly' THEN 1 WHEN plan_type = 'daily' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->first();

        \Log::info('Waiter Dashboard - Specific Plan Search', [
            'found_specific_plan' => $activeMenuPlan ? 'yes' : 'no',
            'plan_name' => $activeMenuPlan ? $activeMenuPlan->plan_name : null,
            'is_active' => $activeMenuPlan ? $activeMenuPlan->is_active : null,
        ]);

        $isDefaultPlan = false;
        $dishesCollection = collect();

        if (!$activeMenuPlan) {
            // Fall back to the default plan
            $activeMenuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
                ->where('restaurant_id', $restaurantId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            \Log::info('Waiter Dashboard - Default Plan Fallback', [
                'found_default_plan' => $activeMenuPlan ? 'yes' : 'no',
                'plan_name' => $activeMenuPlan ? $activeMenuPlan->plan_name : null,
                'is_default' => $activeMenuPlan ? $activeMenuPlan->is_default : null,
            ]);

            $isDefaultPlan = true;
        }

        if ($activeMenuPlan) {
            if ($isDefaultPlan) {
                // For default plans, show all dishes regardless of planned_date
                $dishesCollection = $activeMenuPlan->menuPlanDishes()
                    ->with(['dish.category', 'dish.dishIngredients.ingredient', 'dish.variants'])
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish')
                    ->filter(function ($dish) {
                        // Check if dish has available stock for all ingredients
                        return $dish->hasAvailableStock(1);
                    });
            } else {
                // For specific plans, filter by today's date
                $dishesCollection = $activeMenuPlan->menuPlanDishes()
                    ->with(['dish.category', 'dish.dishIngredients.ingredient', 'dish.variants'])
                    ->where('planned_date', $today)
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish')
                    ->filter(function ($dish) {
                        // Check if dish has available stock for all ingredients
                        return $dish->hasAvailableStock(1);
                    });
            }
        }

        // Group dishes by category
        $dishesGrouped = $dishesCollection->groupBy('category.category_name');

        // Convert collection to array for proper JSON serialization
        $dishes = [];
        foreach ($dishesGrouped as $categoryName => $categoryDishes) {
            $dishes[$categoryName ?: 'Uncategorized'] = $categoryDishes->toArray();
        }

        return Inertia::render('Waiter/Dashboard', [
            'tables' => $tables,
            'employee' => $employee->load('role'),
            'activeMenuPlan' => $activeMenuPlan,
            'dishes' => $dishes,
            'isDefaultPlan' => $isDefaultPlan,
            'readyOrders' => $this->getReadyOrders($employee->user_id),
        ]);
    }

    public function currentMenu()
    {
        // Get the authenticated employee
        $employee = Auth::guard('waiter')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        // Get the restaurant ID through the Restaurant_Data relationship
        $restaurantData = \App\Models\Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (!$restaurantData) {
            abort(404, 'Restaurant data not found for this employee.');
        }
        $restaurantId = $restaurantData->id;

        // Get today's menu plan using the same logic as the API
        $today = now()->format('Y-m-d');

        \Log::info('Waiter Current Menu - Menu Plan Lookup', [
            'employee_user_id' => $employee->user_id,
            'restaurant_id' => $restaurantId,
            'today' => $today,
            'employee' => $employee->firstname . ' ' . $employee->lastname
        ]);

        // First, try to find a specific menu plan for today
        // Priority: weekly plans over daily plans when both cover the same date
        $activeMenuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderByRaw("CASE WHEN plan_type = 'weekly' THEN 1 WHEN plan_type = 'daily' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->first();

        \Log::info('Waiter Current Menu - Specific Plan Search', [
            'found_specific_plan' => $activeMenuPlan ? 'yes' : 'no',
            'plan_name' => $activeMenuPlan ? $activeMenuPlan->plan_name : null,
            'is_active' => $activeMenuPlan ? $activeMenuPlan->is_active : null,
        ]);

        $isDefaultPlan = false;
        $dishesCollection = collect();

        if (!$activeMenuPlan) {
            // Fall back to the default plan
            $activeMenuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
                ->where('restaurant_id', $restaurantId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            \Log::info('Waiter Current Menu - Default Plan Fallback', [
                'found_default_plan' => $activeMenuPlan ? 'yes' : 'no',
                'plan_name' => $activeMenuPlan ? $activeMenuPlan->plan_name : null,
                'is_default' => $activeMenuPlan ? $activeMenuPlan->is_default : null,
            ]);

            $isDefaultPlan = true;
        }

        if ($activeMenuPlan) {
            if ($isDefaultPlan) {
                // For default plans, show all dishes regardless of planned_date
                $dishesCollection = $activeMenuPlan->menuPlanDishes()
                    ->with(['dish.category', 'dish.dishIngredients.ingredient', 'dish.variants'])
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish')
                    ->filter(function ($dish) {
                        // Check if dish has available stock for all ingredients
                        return $dish->hasAvailableStock(1);
                    });
            } else {
                // For specific plans, filter by today's date
                $dishesCollection = $activeMenuPlan->menuPlanDishes()
                    ->with(['dish.category', 'dish.dishIngredients.ingredient', 'dish.variants'])
                    ->where('planned_date', $today)
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish')
                    ->filter(function ($dish) {
                        // Check if dish has available stock for all ingredients
                        return $dish->hasAvailableStock(1);
                    });
            }
        }

        // Group dishes by category
        $dishesGrouped = $dishesCollection->groupBy('category.category_name');

        // Convert collection to array for proper JSON serialization
        $dishes = [];
        foreach ($dishesGrouped as $categoryName => $categoryDishes) {
            $dishes[$categoryName ?: 'Uncategorized'] = $categoryDishes->toArray();
        }

        return Inertia::render('Waiter/CurrentMenu', [
            'employee' => $employee->load('role'),
            'activeMenuPlan' => $activeMenuPlan,
            'dishes' => $dishes,
            'isDefaultPlan' => $isDefaultPlan,
            'currentDate' => $today,
            'readyOrders' => $this->getReadyOrders($employee->user_id),
        ]);
    }

    public function updateTableStatus(Request $request, Table $table)
    {
        $employee = Auth::guard('waiter')->user();

        // Ensure the table belongs to the same restaurant as the waiter
        if ($table->user_id !== $employee->user_id) {
            abort(403, 'Unauthorized access to table.');
        }

        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance'
        ]);

        $table->update($validated);

        return redirect()->back()->with('success', 'Table status updated successfully.');
    }

    public function takeOrder()
    {
        $employee = Auth::guard('waiter')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        // Get tables for the restaurant this waiter belongs to
        $tables = Table::where('user_id', $employee->user_id)
            ->orderBy('table_number')
            ->get();

        return Inertia::render('Waiter/TakeOrder', [
            'tables' => $tables,
            'employee' => $employee->load('role'),
            'readyOrders' => $this->getReadyOrders($employee->user_id),
        ]);
    }

    public function createOrder(Request $request, $tableId)
    {
        $employee = Auth::guard('waiter')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        // Get the restaurant ID through the Restaurant_Data relationship
        $restaurantData = \App\Models\Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (!$restaurantData) {
            abort(404, 'Restaurant data not found for this employee.');
        }
        $restaurantId = $restaurantData->id;

        // Get the table and ensure it belongs to the same restaurant
        $table = Table::where('id', $tableId)
            ->where('user_id', $employee->user_id)
            ->firstOrFail();

        // Check for existing active order for this table
        $existingOrder = null;
        if ($table->status === 'occupied') {
            $existingOrder = CustomerOrder::where('table_id', $tableId)
                ->whereIn('status', ['pending', 'in_progress', 'ready'])
                ->with(['orderItems.dish'])
                ->latest()
                ->first();
        }

        // Get today's menu plan using the same logic as the API
        $today = now()->format('Y-m-d');

        \Log::info('Waiter Create Order - Menu Plan Lookup', [
            'employee_user_id' => $employee->user_id,
            'restaurant_id' => $restaurantId,
            'today' => $today,
            'table_id' => $tableId
        ]);

        // First, try to find a specific menu plan for today
        // Priority: weekly plans over daily plans when both cover the same date
        $activeMenuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderByRaw("CASE WHEN plan_type = 'weekly' THEN 1 WHEN plan_type = 'daily' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->first();

        \Log::info('Waiter Create Order - Specific Plan Search', [
            'found_specific_plan' => $activeMenuPlan ? 'yes' : 'no',
            'plan_name' => $activeMenuPlan ? $activeMenuPlan->plan_name : null,
        ]);

        $isDefaultPlan = false;

        if (!$activeMenuPlan) {
            // Fall back to the default plan
            $activeMenuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
                ->where('restaurant_id', $restaurantId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            \Log::info('Waiter Create Order - Default Plan Fallback', [
                'found_default_plan' => $activeMenuPlan ? 'yes' : 'no',
                'plan_name' => $activeMenuPlan ? $activeMenuPlan->plan_name : null,
            ]);

            $isDefaultPlan = true;
        }

        $dishes = collect();

        if ($activeMenuPlan) {
            if ($isDefaultPlan) {
                // For default plans, show all dishes regardless of planned_date
                $dishes = $activeMenuPlan->menuPlanDishes()
                    ->with(['dish.category', 'dish.dishIngredients.ingredient', 'dish.variants'])
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish')
                    ->filter(function ($dish) {
                        // Check if dish has available stock for all ingredients
                        return $dish->hasAvailableStock(1);
                    });
            } else {
                // For specific plans, filter by today's date
                $dishes = $activeMenuPlan->menuPlanDishes()
                    ->with(['dish.category', 'dish.dishIngredients.ingredient', 'dish.variants'])
                    ->where('planned_date', $today)
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish')
                    ->filter(function ($dish) {
                        // Check if dish has available stock for all ingredients
                        return $dish->hasAvailableStock(1);
                    });
            }
        }

        return Inertia::render('Waiter/CreateOrder', [
            'table' => $table,
            'dishes' => $dishes->values()->toArray(), // Convert Collection to array
            'employee' => $employee->load('role'),
            'existingOrder' => $existingOrder,
            'activeMenuPlan' => $activeMenuPlan,
            'isDefaultPlan' => $isDefaultPlan,
            'currentDate' => $today,
            'readyOrders' => $this->getReadyOrders($employee->user_id),
        ]);
    }

   // Assuming the required models are imported at the top (DB, CustomerOrder, OrderSequence, etc.)

public function storeOrder(Request $request)
    {
        $employee = Auth::guard('waiter')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        // Get the restaurant ID through the Restaurant_Data relationship
        $restaurantData = \App\Models\Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (!$restaurantData) {
            abort(404, 'Restaurant data not found for this employee.');
        }
        $restaurantId = $restaurantData->id;

        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'order_items' => 'required|array|min:1',
            'order_items.*.dish_id' => 'required|exists:dishes,dish_id',
            'order_items.*.variant_id' => 'nullable|exists:dish_variants,variant_id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.special_instructions' => 'nullable|string',
            'order_items.*.excluded_ingredients' => 'nullable|array',
            'order_items.*.excluded_ingredients.*' => 'exists:ingredients,ingredient_id',
        ]);

        // Verify table belongs to the same restaurant
        $table = Table::where('id', $validated['table_id'])
            ->where('user_id', $employee->user_id)
            ->firstOrFail();

        // Check for existing active order on this table (regardless of table status)
        $existingOrder = CustomerOrder::where('table_id', $validated['table_id'])
            ->whereIn('status', ['pending', 'in_progress', 'ready'])
            ->latest()
            ->first();

        DB::transaction(function () use ($validated, $employee, $table, $existingOrder, $restaurantId) {

            if ($existingOrder) {
                // Add items to existing order
                $order = $existingOrder;

                // Check if order doesn't have reservation but table has active reservation
                if (!$order->reservation_id) {
                    $activeReservation = TableReservation::where('table_id', $validated['table_id'])
                        ->whereIn('status', ['pending', 'confirmed', 'seated'])
                        ->where('reservation_date', '>=', now()->startOfDay())
                        ->orderBy('reservation_date', 'asc')
                        ->first();

                    if ($activeReservation) {
                        $order->reservation_id = $activeReservation->id;
                        $order->reservation_fee = $activeReservation->reservation_fee ?? 0;
                        \Log::info('Adding reservation to existing order', [
                            'order_id' => $order->order_id,
                            'reservation_id' => $activeReservation->id,
                            'reservation_fee' => $activeReservation->reservation_fee,
                        ]);
                    }
                }

                // Update customer name and notes if provided
                if (!empty($validated['customer_name']) && empty($order->customer_name)) {
                    $order->customer_name = $validated['customer_name'];
                }
                if (!empty($validated['notes'])) {
                    $order->notes = trim($order->notes . ' ' . $validated['notes']);
                }
                $order->save();
            } else {
                // Check for active reservation on this table (for today or future dates)
                $activeReservation = TableReservation::where('table_id', $validated['table_id'])
                    ->whereIn('status', ['pending', 'confirmed', 'seated'])
                    ->where('reservation_date', '>=', now()->startOfDay())
                    ->orderBy('reservation_date', 'asc')
                    ->first();

                \Log::info('Waiter creating order - Reservation check', [
                    'table_id' => $validated['table_id'],
                    'active_reservation_found' => $activeReservation ? 'Yes' : 'No',
                    'reservation_id' => $activeReservation?->id,
                    'reservation_fee' => $activeReservation?->reservation_fee,
                    'reservation_date' => $activeReservation?->reservation_date,
                ]);

                // Create new order
                $orderData = [
                    'table_id' => $validated['table_id'],
                    'employee_id' => $employee->employee_id,
                    'restaurant_id' => $employee->user_id,
                    'customer_name' => $validated['customer_name'],
                    'notes' => $validated['notes'],
                    'status' => 'pending',
                    'ordered_at' => now(),
                ];

                // Add reservation info if exists
                if ($activeReservation) {
                    $orderData['reservation_id'] = $activeReservation->id;
                    $orderData['reservation_fee'] = $activeReservation->reservation_fee ?? 0;
                    \Log::info('Adding reservation to order', $orderData);
                }

                $order = CustomerOrder::create($orderData);
            }

            // Add order items
            foreach ($validated['order_items'] as $item) {
                $dish = Dish::find($item['dish_id']);

                // Determine unit price based on variant or base price
                $unitPrice = $dish->price;
                if (!empty($item['variant_id'])) {
                    $variant = \App\Models\DishVariant::find($item['variant_id']);
                    if ($variant) {
                        $unitPrice = $variant->price_modifier;
                    }
                }

                // Save customer requests for excluded ingredients FIRST (before creating order item)
                // This ensures the exclusions are already in the database when inventory deduction happens
                if (!empty($item['excluded_ingredients'])) {
                    foreach ($item['excluded_ingredients'] as $ingredientId) {
                        CustomerRequest::create([
                            'order_id' => $order->order_id,
                            'dish_id' => $item['dish_id'],
                            'ingredient_id' => $ingredientId,
                            'restaurant_id' => $restaurantId,
                            'request_type' => 'exclude',
                            'notes' => 'Customer requested to exclude this ingredient',
                        ]);
                    }
                }

                // Check if this dish already exists in the order with same variant
                $existingItem = CustomerOrderItem::where('order_id', $order->order_id)
                    ->where('dish_id', $item['dish_id'])
                    ->where('variant_id', $item['variant_id'] ?? null)
                    ->where('special_instructions', $item['special_instructions'] ?? null)
                    ->first();

                if ($existingItem) {
                    // Update quantity of existing item and deduct inventory for additional quantity
                    $additionalQuantity = $item['quantity'];
                    $existingItem->quantity += $additionalQuantity;
                    $existingItem->save();

                    // Manually deduct inventory for the additional quantity
                    $existingItem->load('variant'); // Ensure variant is loaded
                    $existingItem->deductIngredientsForQuantity($additionalQuantity);
                } else {
                    // Create new order item (this triggers inventory deduction)
                    CustomerOrderItem::create([
                        'order_id' => $order->order_id,
                        'dish_id' => $item['dish_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'special_instructions' => $item['special_instructions'] ?? null,
                        'status' => 'pending',
                    ]);
                }
            }

            // Update table status to occupied if it was available
            if ($table->status === 'available') {
                $table->update(['status' => 'occupied']);
            }

            // Recalculate order totals
            $order->calculateTotals();
            
            // Broadcast the order created/updated event
            if (!$existingOrder) {
                // Only broadcast for new orders
                broadcast(new OrderCreated($order))->toOthers();
            }
        });

        return redirect()
            ->route('waiter.dashboard')
            ->with('success', $existingOrder ? 'Items added to existing order successfully!' : 'Order created successfully!');
    }

    public function getTableOrders($tableId)
    {
        $employee = Auth::guard('waiter')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'waiter') {
            return response()->json(['error' => 'Access denied. Waiters only.'], 403);
        }

        try {
            // Get orders for this table with order items and dishes (only unpaid orders)
            $orders = CustomerOrder::with(['orderItems.dish', 'orderItems.variant', 'table', 'employee'])
                ->where('table_id', $tableId)
                ->where('restaurant_id', $employee->user_id) // Filter by restaurant
                ->whereNotIn('status', ['paid', 'cancelled', 'completed', 'voided']) // Exclude paid, cancelled, completed, and voided orders
                ->orderBy('created_at', 'desc')
                ->get();

            // Transform the data to ensure frontend compatibility
            $transformedOrders = $orders->map(function ($order) {
                return [
                    'order_id' => $order->order_id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'customer_name' => $order->customer_name,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'table' => $order->table,
                    'employee' => $order->employee,
                    'order_items' => $order->orderItems->map(function ($item) {
                        return [
                            'item_id' => $item->item_id,
                            'dish_id' => $item->dish_id,
                            'variant_id' => $item->variant_id,
                            'quantity' => $item->quantity,
                            'served_quantity' => $item->served_quantity,
                            'unit_price' => $item->unit_price,
                            'special_instructions' => $item->special_instructions,
                            'status' => $item->status,
                            'dish' => $item->dish,
                            'variant' => $item->variant
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'orders' => $transformedOrders
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching table orders:', [
                'error' => $e->getMessage(),
                'table_id' => $tableId,
                'employee_id' => $employee->employee_id
            ]);

            return response()->json([
                'error' => 'Failed to fetch orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateItemServedStatus(Request $request, $orderId, $itemId)
    {
        $employee = Auth::guard('waiter')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'waiter') {
            return response()->json(['error' => 'Access denied. Waiters only.'], 403);
        }

        $request->validate([
            'served' => 'required|boolean'
        ]);

        try {
            \Log::info('Updating item served status', [
                'order_id' => $orderId,
                'item_id' => $itemId,
                'served' => $request->served,
                'employee_id' => $employee->employee_id,
                'user_id' => $employee->user_id
            ]);

            // Find the order item
            $orderItem = CustomerOrderItem::where('item_id', $itemId)
                ->whereHas('customerOrder', function ($query) use ($orderId, $employee) {
                    $query->where('order_id', $orderId)
                          ->where('restaurant_id', $employee->user_id);
                })
                ->first();

            if (!$orderItem) {
                \Log::error('Order item not found', [
                    'order_id' => $orderId,
                    'item_id' => $itemId,
                    'employee_user_id' => $employee->user_id
                ]);
                return response()->json(['error' => 'Order item not found'], 404);
            }

            \Log::info('Found order item', [
                'item_id' => $orderItem->item_id,
                'current_quantity' => $orderItem->quantity,
                'current_served_quantity' => $orderItem->served_quantity,
                'current_status' => $orderItem->status
            ]);

            if ($request->served) {
                // Increment served_quantity by 1 (but not exceeding total quantity)
                if ($orderItem->served_quantity < $orderItem->quantity) {
                    $orderItem->served_quantity += 1;

                    // If all items are served, mark status as 'served'
                    if ($orderItem->served_quantity >= $orderItem->quantity) {
                        $orderItem->status = 'served';
                    }
                }
            } else {
                // Decrement served_quantity by 1 (but not below 0)
                if ($orderItem->served_quantity > 0) {
                    $orderItem->served_quantity -= 1;

                    // If some items are not served, mark status as 'pending'
                    if ($orderItem->served_quantity < $orderItem->quantity) {
                        $orderItem->status = 'pending';
                    }
                }
            }

            $orderItem->save();

            \Log::info('Order item updated successfully', [
                'item_id' => $orderItem->item_id,
                'new_quantity' => $orderItem->quantity,
                'new_served_quantity' => $orderItem->served_quantity,
                'new_status' => $orderItem->status
            ]);

            // Check if any items were served and broadcast event
            if ($request->served && $orderItem->served_quantity > 0) {
                $order = $orderItem->customerOrder;
                broadcast(new OrderServed($order))->toOthers();
            }

            return response()->json([
                'success' => true,
                'message' => 'Item status updated successfully',
                'item' => [
                    'item_id' => $orderItem->item_id,
                    'quantity' => $orderItem->quantity,
                    'served_quantity' => $orderItem->served_quantity,
                    'status' => $orderItem->status
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating item served status:', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'item_id' => $itemId,
                'employee_id' => $employee->employee_id
            ]);

            return response()->json([
                'error' => 'Failed to update item status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check dish ingredient availability considering current cart items
     */
    public function checkDishAvailability(Request $request)
    {
        $employee = Auth::guard('waiter')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'waiter') {
            return response()->json(['error' => 'Access denied. Waiters only.'], 403);
        }

        $validated = $request->validate([
            'dish_id' => 'required|exists:dishes,dish_id',
            'variant_id' => 'nullable|exists:dish_variants,variant_id',
            'requested_quantity' => 'required|integer|min:1',
            'cart_items' => 'nullable|array',
            'cart_items.*.dish_id' => 'required|exists:dishes,dish_id',
            'cart_items.*.variant_id' => 'nullable|exists:dish_variants,variant_id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.excluded_ingredients' => 'nullable|array',
            'cart_items.*.excluded_ingredients.*' => 'exists:ingredients,ingredient_id',
        ]);

        try {
            // Get the dish with ingredients
            $dish = Dish::with(['dishIngredients.ingredient', 'variants'])
                ->where('restaurant_id', $employee->user_id)
                ->findOrFail($validated['dish_id']);

            // Get variant multiplier
            $variantMultiplier = 1.0;
            if (!empty($validated['variant_id'])) {
                $variant = $dish->variants->where('variant_id', $validated['variant_id'])->first();
                if ($variant) {
                    $variantMultiplier = $variant->quantity_multiplier ?? 1.0;
                }
            }

            // Calculate total ingredient requirements from cart
            $cartIngredientRequirements = [];
            if (!empty($validated['cart_items'])) {
                foreach ($validated['cart_items'] as $cartItem) {
                    $cartDish = Dish::with(['dishIngredients.ingredient', 'variants'])
                        ->where('restaurant_id', $employee->user_id)
                        ->find($cartItem['dish_id']);

                    if (!$cartDish) continue;

                    // Get variant multiplier for cart item
                    $cartVariantMultiplier = 1.0;
                    if (!empty($cartItem['variant_id'])) {
                        $cartVariant = $cartDish->variants->where('variant_id', $cartItem['variant_id'])->first();
                        if ($cartVariant) {
                            $cartVariantMultiplier = $cartVariant->quantity_multiplier ?? 1.0;
                        }
                    }

                    // Get excluded ingredients for this cart item
                    $excludedIngredients = $cartItem['excluded_ingredients'] ?? [];

                    // Add ingredient requirements for this cart item
                    foreach ($cartDish->dishIngredients as $dishIngredient) {
                        // Skip if ingredient is excluded in this cart item
                        if (in_array($dishIngredient->ingredient_id, $excludedIngredients)) {
                            continue;
                        }

                        $quantityInBaseUnit = $dishIngredient->getQuantityInBaseUnit();
                        $totalRequired = $quantityInBaseUnit * $cartVariantMultiplier * $cartItem['quantity'];

                        if (!isset($cartIngredientRequirements[$dishIngredient->ingredient_id])) {
                            $cartIngredientRequirements[$dishIngredient->ingredient_id] = 0;
                        }
                        $cartIngredientRequirements[$dishIngredient->ingredient_id] += $totalRequired;
                    }
                }
            }

            // Check availability for the requested dish
            $ingredientDetails = [];
            $isAvailable = true;
            $maxQuantity = PHP_INT_MAX;
            $limitingIngredient = null;

            foreach ($dish->dishIngredients as $dishIngredient) {
                $ingredient = $dishIngredient->ingredient;
                $quantityInBaseUnit = $dishIngredient->getQuantityInBaseUnit();
                $requiredPerDish = $quantityInBaseUnit * $variantMultiplier;

                // Calculate total required (cart + requested)
                $cartUsage = $cartIngredientRequirements[$ingredient->ingredient_id] ?? 0;
                $requestedUsage = $requiredPerDish * $validated['requested_quantity'];
                $totalRequired = $cartUsage + $requestedUsage;

                // Check current stock
                $currentStock = $ingredient->current_stock;
                $availableStock = $currentStock - $cartUsage;

                // Calculate max quantity possible for this ingredient
                $maxForThisIngredient = $availableStock > 0
                    ? floor($availableStock / $requiredPerDish)
                    : 0;

                if ($maxForThisIngredient < $maxQuantity) {
                    $maxQuantity = $maxForThisIngredient;
                    $limitingIngredient = $ingredient->ingredient_name;
                }

                $ingredientDetails[] = [
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'current_stock' => $currentStock,
                    'base_unit' => $ingredient->base_unit,
                    'required_per_dish' => round($requiredPerDish, 4),
                    'cart_usage' => round($cartUsage, 4),
                    'requested_usage' => round($requestedUsage, 4),
                    'total_required' => round($totalRequired, 4),
                    'available_after_cart' => round($availableStock, 4),
                    'is_sufficient' => $totalRequired <= $currentStock,
                    'max_quantity' => (int)$maxForThisIngredient,
                ];

                if ($totalRequired > $currentStock) {
                    $isAvailable = false;
                }
            }

            return response()->json([
                'success' => true,
                'available' => $isAvailable,
                'max_quantity' => max(0, (int)$maxQuantity),
                'limiting_ingredient' => $limitingIngredient,
                'dish_name' => $dish->dish_name,
                'requested_quantity' => $validated['requested_quantity'],
                'ingredients' => $ingredientDetails,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error checking dish availability:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'dish_id' => $validated['dish_id'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to check dish availability',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}