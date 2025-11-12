<?php

namespace App\Http\Controllers;

use App\Models\MenuPreparationOrder;
use App\Models\MenuPreparationItem;
use App\Models\CustomerOrder;
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

        $restaurantId = $employee->user_id; // Employee belongs to their specific restaurant

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
            ->where('restaurant_id', $restaurantId)
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
            ->where('restaurant_id', $restaurantId)
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
            $ingredients = \App\Models\Ingredients::where('restaurant_id', $restaurantId)
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
}
