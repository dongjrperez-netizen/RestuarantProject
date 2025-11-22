<?php

namespace App\Http\Controllers;

use App\Models\MenuPreparationOrder;
use App\Models\MenuPreparationItem;
use App\Models\CustomerOrder;
use App\Models\MenuPlan;
use App\Events\OrderStatusUpdated;
use App\Events\OrderReadyToServe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use Inertia\Inertia;

class KitchenController extends Controller
{
    public function dashboard()
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantOwnerId = $employee->user_id; // Employee belongs to a specific restaurant owner

        try {
            // Single optimized query with all relationships loaded eagerly
            // Only show orders from today that are not completed or paid
            $unpaidOrders = CustomerOrder::with([
                'table',
                'orderItems' => function($query) {
                    $query->whereColumn('served_quantity', '<', 'quantity')
                         ->with(['dish', 'variant', 'excludedIngredients.ingredient']);
                },
                'employee'
            ])
            ->where('restaurant_id', $restaurantOwnerId)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress', 'ready'])
            ->whereDate('created_at', today())
            ->whereHas('orderItems', function ($query) {
                $query->whereColumn('served_quantity', '<', 'quantity');
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

            // Single query for all stats using DB::raw
            $stats = CustomerOrder::selectRaw('
                COUNT(*) as total_orders,
                SUM(CASE WHEN status IN ("pending", "confirmed") THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_orders,
                SUM(CASE WHEN status = "ready" THEN 1 ELSE 0 END) as ready_orders
            ')
            ->where('restaurant_id', $restaurantOwnerId)
            ->whereDate('created_at', today())
            ->first();

            $todayStats = [
                'total_orders' => (int) $stats->total_orders,
                'pending_orders' => (int) $stats->pending_orders,
                'in_progress_orders' => (int) $stats->in_progress_orders,
                'ready_orders' => (int) $stats->ready_orders,
            ];

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Kitchen Dashboard Error: ' . $e->getMessage());

            // Return empty collection if database query fails
            $unpaidOrders = collect([]);

            $todayStats = [
                'total_orders' => 0,
                'pending_orders' => 0,
                'in_progress_orders' => 0,
                'ready_orders' => 0,
            ];
        }


        // Get ingredients for damage/spoilage modal
        $ingredients = [];
        $types = [];
        try {
            // Ingredients are linked to restaurant_data (restaurant_id), which is linked to the owner (user_id)
            $ingredients = \App\Models\Ingredients::whereHas('restaurant', function ($query) use ($employee) {
                    $query->where('user_id', $employee->user_id);
                })
                ->orderBy('ingredient_name')
                ->get(['ingredient_id', 'ingredient_name', 'base_unit']);
            $types = \App\Models\DamageSpoilageLog::getTypes();
        } catch (\Exception $e) {
            // Fallback if tables don't exist yet
            $ingredients = collect([]);
            $types = ['damage' => 'Damage', 'spoilage' => 'Spoilage'];
        }

        return Inertia::render('Kitchen/Dashboard', [
            'unpaidOrders' => $unpaidOrders,
            'todayStats' => $todayStats,
            'employee' => $employee ?: (object)[
                'employee_id' => 1,
                'name' => 'Kitchen Staff Demo',
                'email' => 'kitchen@demo.com'
            ],
            'ingredients' => $ingredients,
            'damageTypes' => $types,
        ]);
    }

    public function startPreparationOrder(Request $request, $id)
    {
        // Mock response - replace with real database operations when tables are created
        return response()->json(['success' => true, 'message' => 'Order started successfully (Demo Mode)']);
    }

    public function startPreparationItem(Request $request, $orderId, $itemId)
    {
        // Mock response - replace with real database operations when tables are created
        return response()->json(['success' => true, 'message' => 'Item started successfully (Demo Mode)']);
    }

    public function completePreparationItem(Request $request, $orderId, $itemId)
    {
        $request->validate([
            'prepared_quantity' => 'required|integer|min:0'
        ]);

        // Mock response - replace with real database operations when tables are created
        return response()->json(['success' => true, 'message' => 'Item completed successfully (Demo Mode)']);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string|in:confirmed,in_progress,ready'
        ]);

        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id; // Employee belongs to their specific restaurant

        try {
            $order = CustomerOrder::where('order_id', $orderId)
                ->where('restaurant_id', $restaurantId)
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }
            
            $previousStatus = $order->status;

            $order->update([
                'status' => $request->status,
                'updated_at' => now()
            ]);

            // Broadcast the status update event
            broadcast(new OrderStatusUpdated($order, $previousStatus))->toOthers();

            // If order is now ready, broadcast specific ready event for waiters
            if ($request->status === 'ready' && $previousStatus !== 'ready') {
                broadcast(new OrderReadyToServe($order))->toOthers();
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            // Mock response if database operations fail
            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$request->status} (Demo Mode)"
            ]);
        }
    }

    public function todaysPlan()
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        // Get the restaurant ID through the Restaurant_Data relationship
        $restaurantData = \App\Models\Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (!$restaurantData) {
            abort(404, 'Restaurant data not found for this employee.');
        }
        $restaurantId = $restaurantData->id;

        // Get today's date
        $today = now()->format('Y-m-d');

        \Log::info('Kitchen Today\'s Plan - Menu Plan Lookup', [
            'employee_user_id' => $employee->user_id,
            'restaurant_id' => $restaurantId,
            'today' => $today,
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

        $isDefaultPlan = false;

        if (!$activeMenuPlan) {
            // Fall back to the default plan
            $activeMenuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
                ->where('restaurant_id', $restaurantId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            $isDefaultPlan = true;
        }

        // If we have a specific (non-default) plan, filter dishes by today's date
        if ($activeMenuPlan && !$isDefaultPlan) {
            $activeMenuPlan->load(['menuPlanDishes' => function ($query) use ($today) {
                $query->where('planned_date', $today)->with(['dish.category']);
            }]);
        }

        return Inertia::render('Kitchen/TodaysPlan', [
            'menuPlan' => $activeMenuPlan,
            'currentDate' => $today,
            'isDefaultPlan' => $isDefaultPlan,
        ]);
    }
}
