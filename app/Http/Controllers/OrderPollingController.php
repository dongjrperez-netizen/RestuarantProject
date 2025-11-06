<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderPollingController extends Controller
{
    public function kitchenOrders()
    {
        $employee = Auth::guard('kitchen')->user();
        if (!$employee) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $restaurantId = $employee->user_id;

        $orders = CustomerOrder::with(['table', 'orderItems.dish', 'orderItems.variant', 'employee'])
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'ready', 'completed', 'in_progress'])
            ->whereHas('orderItems', function ($query) {
                $query->whereColumn('served_quantity', '<', 'quantity');
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Load excluded ingredients for each order item
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $item->excluded_ingredients = \App\Models\CustomerRequest::where('order_id', $order->order_id)
                    ->where('dish_id', $item->dish_id)
                    ->where('request_type', 'exclude')
                    ->with('ingredient')
                    ->get();
            }
        }

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'timestamp' => now()->toISOString()
        ]);
    }

    public function cashierOrders()
    {
        $employee = Auth::guard('cashier')->user();
        if (!$employee) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $restaurantId = $employee->user_id;

        $orders = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments', 'reservation'])
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'ready', 'completed', 'in_progress'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        // Add payment data to each order for frontend compatibility
        $orders->getCollection()->transform(function ($order) {
            $payment = $order->payments()->latest()->first();
            if ($payment) {
                $order->discount_amount = $payment->discount_amount;
                $order->final_amount = $payment->final_amount;
                $order->payment_method = $payment->payment_method;
                $order->amount_paid = $payment->amount_paid;
                $order->paid_at = $payment->paid_at;
                $order->payment_notes = $payment->notes;
            }
            return $order;
        });

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'timestamp' => now()->toISOString()
        ]);
    }
}