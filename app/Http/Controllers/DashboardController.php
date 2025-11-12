<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\CustomerPayment;
use App\Models\Ingredients;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $restaurantId = $user->id;

        // Get dashboard statistics
        $stats = $this->getDashboardStats($restaurantId);

        // Get recent orders
        $recentOrders = $this->getRecentOrders($restaurantId);

        // Get low stock items
        $lowStockItems = $this->getLowStockItems($restaurantId);

        // Get weekly performance data
        $weeklyData = $this->getWeeklyPerformanceData($restaurantId);

        // Get order status distribution
        $orderStatusData = $this->getOrderStatusDistribution($restaurantId);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'lowStockItems' => $lowStockItems,
            'weeklyData' => $weeklyData,
            'orderStatusData' => $orderStatusData,
        ]);
    }

    private function getDashboardStats($restaurantId)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->startOfMonth();

        // Today's revenue (sum final_amount from completed payments)
        $todayRevenue = CustomerPayment::join('customer_orders', 'customer_payments.order_id', '=', 'customer_orders.order_id')
            ->where('customer_orders.restaurant_id', $restaurantId)
            ->whereDate('customer_payments.paid_at', $today)
            ->where('customer_payments.status', 'completed')
            ->sum('customer_payments.final_amount');

        // Yesterday's revenue for growth calculation
        $yesterdayRevenue = CustomerPayment::join('customer_orders', 'customer_payments.order_id', '=', 'customer_orders.order_id')
            ->where('customer_orders.restaurant_id', $restaurantId)
            ->whereDate('customer_payments.paid_at', $yesterday)
            ->where('customer_payments.status', 'completed')
            ->sum('customer_payments.final_amount');

        // This month's revenue
        $thisMonthRevenue = CustomerPayment::join('customer_orders', 'customer_payments.order_id', '=', 'customer_orders.order_id')
            ->where('customer_orders.restaurant_id', $restaurantId)
            ->where('customer_payments.paid_at', '>=', $thisMonth)
            ->where('customer_payments.status', 'completed')
            ->sum('customer_payments.final_amount');

        // Calculate growth percentage
        $growth = 0;
        if ($yesterdayRevenue > 0) {
            $growth = (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100;
        } elseif ($todayRevenue > 0) {
            $growth = 100; // If yesterday was 0 but today has revenue
        }

        // Today's orders
        $todayOrders = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->count();

        $pendingOrders = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $completedTodayOrders = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        // Employee stats
        $activeEmployees = Employee::where('user_id', $restaurantId)
            ->where('status', 'active')
            ->count();

        $totalEmployees = Employee::where('user_id', $restaurantId)
            ->count();

        // Inventory stats
        $lowStockCount = Ingredients::where('restaurant_id', $restaurantId)
            ->whereNotNull('reorder_level')
            ->whereRaw('CAST(current_stock AS DECIMAL(10,2)) <= CAST(reorder_level AS DECIMAL(10,2))')
            ->count();

        $totalItems = Ingredients::where('restaurant_id', $restaurantId)
            ->count();

        return [
            'revenue' => [
                'today' => round($todayRevenue, 2),
                'thisMonth' => round($thisMonthRevenue, 2),
                'growth' => round($growth, 1),
            ],
            'orders' => [
                'today' => $todayOrders,
                'pending' => $pendingOrders,
                'completed' => $completedTodayOrders,
            ],
            'inventory' => [
                'lowStock' => $lowStockCount,
                'totalItems' => $totalItems,
            ],
            'employees' => [
                'active' => $activeEmployees,
                'total' => $totalEmployees,
            ],
        ];
    }

    private function getRecentOrders($restaurantId)
    {
        return CustomerOrder::where('restaurant_id', $restaurantId)
            ->with(['orderItems'])
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->order_id,
                    'orderNumber' => $order->order_number,
                    'customerName' => $order->customer_name,
                    'total' => round($order->total_amount, 2),
                    'status' => $this->mapOrderStatus($order->status),
                    'items' => $order->orderItems->count(),
                    'time' => $order->created_at->diffForHumans(),
                ];
            });
    }

    private function getLowStockItems($restaurantId)
    {
        return Ingredients::where('restaurant_id', $restaurantId)
            ->whereNotNull('reorder_level')
            ->whereRaw('CAST(current_stock AS DECIMAL(10,2)) <= CAST(reorder_level AS DECIMAL(10,2))')
            ->orderBy('current_stock', 'asc')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->ingredient_id,
                    'name' => $item->ingredient_name,
                    'currentStock' => $item->current_stock,
                    'minStock' => $item->reorder_level,
                    'unit' => $item->base_unit,
                ];
            });
    }

    private function getWeeklyPerformanceData($restaurantId)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $weeklyData = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);

            $dailyRevenue = CustomerPayment::join('customer_orders', 'customer_payments.order_id', '=', 'customer_orders.order_id')
                ->where('customer_orders.restaurant_id', $restaurantId)
                ->whereDate('customer_payments.paid_at', $date)
                ->where('customer_payments.status', 'completed')
                ->sum('customer_payments.final_amount');

            $dailyOrders = CustomerOrder::where('restaurant_id', $restaurantId)
                ->whereDate('created_at', $date)
                ->count();

            $weeklyData[] = [
                'day' => $date->format('D'), // Mon, Tue, Wed, etc.
                'fullDay' => $date->format('l'), // Monday, Tuesday, Wednesday, etc.
                'date' => $date->format('M d'), // Oct 13, Oct 14, etc.
                'revenue' => round($dailyRevenue, 2),
                'orders' => $dailyOrders,
                'Revenue (₱)' => round($dailyRevenue, 2),
                'Orders' => $dailyOrders,
            ];
        }

        return $weeklyData;
    }

    private function getOrderStatusDistribution($restaurantId)
    {
        $statusCounts = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', '>=', Carbon::now()->subDays(30)) // Last 30 days
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $total = $statusCounts->sum('count');
        $mappedData = [];

        foreach ($statusCounts as $status) {
            $mappedStatus = $this->mapOrderStatusForChart($status->status);
            $percentage = $total > 0 ? round(($status->count / $total) * 100) : 0;

            $mappedData[] = [
                'status' => $mappedStatus,
                'count' => $status->count,
                'percentage' => $percentage,
            ];
        }

        // Ensure we have all status types, even if count is 0
        $requiredStatuses = ['Completed', 'Pending', 'Preparing', 'Ready'];
        foreach ($requiredStatuses as $requiredStatus) {
            $exists = collect($mappedData)->contains('status', $requiredStatus);
            if (!$exists) {
                $mappedData[] = [
                    'status' => $requiredStatus,
                    'count' => 0,
                    'percentage' => 0,
                ];
            }
        }

        return $mappedData;
    }

    private function mapOrderStatus($status)
    {
        $statusMap = [
            'pending' => 'pending',
            'in_progress' => 'preparing',
            'ready' => 'ready',
            'served' => 'completed',
            'paid' => 'completed',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[$status] ?? 'pending';
    }

    private function mapOrderStatusForChart($status)
    {
        $statusMap = [
            'pending' => 'Pending',
            'in_progress' => 'Preparing',
            'ready' => 'Ready',
            'served' => 'Completed',
            'paid' => 'Completed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return $statusMap[$status] ?? 'Pending';
    }
}