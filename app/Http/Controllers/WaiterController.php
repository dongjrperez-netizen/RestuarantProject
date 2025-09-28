<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Employee;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Dish;
use App\Models\MenuPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WaiterController extends Controller
{
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
                    ->with(['dish.category'])
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish');
            } else {
                // For specific plans, filter by today's date
                $dishesCollection = $activeMenuPlan->menuPlanDishes()
                    ->with(['dish.category'])
                    ->where('planned_date', $today)
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish');
            }
        }

        // Debug: If no dishes found, try getting all available dishes for this restaurant only
        if ($dishesCollection->isEmpty()) {
            $allDishes = Dish::with(['category'])
                ->where('restaurant_id', $restaurantId)
                ->where('is_available', true)
                ->where('status', 'active')
                ->get();

            if ($allDishes->isNotEmpty()) {
                $dishesCollection = $allDishes;
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
            'currentDate' => $today,
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
                    ->with(['dish.category'])
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish');
            } else {
                // For specific plans, filter by today's date
                $dishes = $activeMenuPlan->menuPlanDishes()
                    ->with(['dish.category'])
                    ->where('planned_date', $today)
                    ->whereHas('dish', function ($query) use ($restaurantId) {
                        $query->where('restaurant_id', $restaurantId)
                              ->where('is_available', true)
                              ->where('status', 'active');
                    })
                    ->get()
                    ->pluck('dish');
            }
        }

        // Debug: If no dishes found, try getting all available dishes for this restaurant only
        if ($dishes->isEmpty()) {
            $allDishes = Dish::with(['category'])
                ->where('restaurant_id', $restaurantId)
                ->where('is_available', true)
                ->where('status', 'active')
                ->get();

            if ($allDishes->isNotEmpty()) {
                $dishes = $allDishes;
            }
        }

        return Inertia::render('Waiter/CreateOrder', [
            'table' => $table,
            'dishes' => $dishes,
            'employee' => $employee->load('role'),
            'existingOrder' => $existingOrder,
            'activeMenuPlan' => $activeMenuPlan,
            'isDefaultPlan' => $isDefaultPlan,
            'currentDate' => $today,
        ]);
    }

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
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.special_instructions' => 'nullable|string',
        ]);

        // Verify table belongs to the same restaurant
        $table = Table::where('id', $validated['table_id'])
            ->where('user_id', $employee->user_id)
            ->firstOrFail();

        // Check for existing active order for occupied tables
        $existingOrder = null;
        if ($table->status === 'occupied') {
            $existingOrder = CustomerOrder::where('table_id', $validated['table_id'])
                ->whereIn('status', ['pending', 'in_progress', 'ready'])
                ->latest()
                ->first();
        }

        DB::transaction(function () use ($validated, $employee, $table, $existingOrder, $restaurantId) {

            if ($existingOrder) {
                // Add items to existing order
                $order = $existingOrder;

                // Update customer name and notes if provided
                if (!empty($validated['customer_name']) && empty($order->customer_name)) {
                    $order->customer_name = $validated['customer_name'];
                }
                if (!empty($validated['notes'])) {
                    $order->notes = trim($order->notes . ' ' . $validated['notes']);
                }
                $order->save();
            } else {
                // Create new order
                $order = CustomerOrder::create([
                    'table_id' => $validated['table_id'],
                    'employee_id' => $employee->employee_id,
                    'restaurant_id' => $employee->user_id,
                    'customer_name' => $validated['customer_name'],
                    'notes' => $validated['notes'],
                    'status' => 'pending',
                    'ordered_at' => now(),
                ]);
            }

            // Add order items
            foreach ($validated['order_items'] as $item) {
                $dish = Dish::find($item['dish_id']);

                // Check if this dish already exists in the order
                $existingItem = CustomerOrderItem::where('order_id', $order->order_id)
                    ->where('dish_id', $item['dish_id'])
                    ->where('special_instructions', $item['special_instructions'] ?? null)
                    ->first();

                if ($existingItem) {
                    // Update quantity of existing item
                    $existingItem->quantity += $item['quantity'];
                    $existingItem->save();
                } else {
                    // Create new order item
                    CustomerOrderItem::create([
                        'order_id' => $order->order_id,
                        'dish_id' => $item['dish_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $dish->price,
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
        });

        return redirect()
            ->route('waiter.dashboard')
            ->with('success', $existingOrder ? 'Items added to existing order successfully!' : 'Order created successfully!');
    }
}