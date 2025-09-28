<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\CustomerPayment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class CashierController extends Controller
{
    public function successfulPayments()
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            abort(403, 'Access denied. Cashiers only.');
        }

        // Get paid orders with payment details
        $paidOrders = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments'])
            ->where('restaurant_id', $employee->user_id)  // Filter by restaurant
            ->where('status', 'paid')  // Only paid orders
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        // Add payment data to each order for frontend compatibility
        $paidOrders->getCollection()->transform(function ($order) {
            $payment = $order->payments()->where('status', 'completed')->latest()->first();
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

        // Get today's revenue statistics
        $todayOrders = CustomerOrder::where('restaurant_id', $employee->user_id)
            ->whereDate('created_at', today())
            ->get();

        $todayRevenue = $todayOrders->where('status', 'paid')->sum('total_amount');
        $todayOrderCount = $todayOrders->where('status', 'paid')->count();
        $pendingOrdersCount = $todayOrders->whereIn('status', ['ready', 'completed'])->count();


        return Inertia::render('Cashier/SuccessfulPayments', [
            'employee' => $employee->load('role'),
            'paidOrders' => $paidOrders,
            'todayRevenue' => $todayRevenue,
            'todayOrderCount' => $todayOrderCount,
            'pendingOrdersCount' => $pendingOrdersCount,
        ]);
    }

    public function bills()
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            abort(403, 'Access denied. Cashiers only.');
        }

        // Get unpaid orders from this restaurant (exclude paid orders)
        $orders = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments'])
            ->where('restaurant_id', $employee->user_id)  // Filter by restaurant
            ->whereIn('status', ['pending', 'ready', 'completed'])  // Exclude paid orders
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

        return Inertia::render('Cashier/Bills', [
            'employee' => $employee->load('role'),
            'orders' => $orders,
        ]);
    }

    public function viewBill($orderId)
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            abort(403, 'Access denied. Cashiers only.');
        }

        // Get the specific order with all details
        $order = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments'])
            ->where('restaurant_id', $employee->user_id)  // Filter by restaurant
            ->where('order_id', $orderId)
            ->firstOrFail();

        // Get the most recent payment for this order (for discount information)
        $payment = $order->payments()->latest()->first();

        // Add payment data to the order object for frontend compatibility
        if ($payment) {
            $order->discount_amount = $payment->discount_amount;
            $order->final_amount = $payment->final_amount;
            $order->payment_method = $payment->payment_method;
            $order->amount_paid = $payment->amount_paid;
            $order->paid_at = $payment->paid_at;
            $order->payment_notes = $payment->notes;
        }

        // Get restaurant details
        $restaurantData = \App\Models\Restaurant_Data::where('user_id', $employee->user_id)->first();

        return Inertia::render('Cashier/ViewBill', [
            'employee' => $employee->load('role'),
            'order' => $order,
            'payment' => $payment,
            'restaurant' => $restaurantData,
        ]);
    }

    public function printBill(Request $request, $orderId)
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            abort(403, 'Access denied. Cashiers only.');
        }

        // Get the specific order with all details including payments
        $order = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments'])
            ->where('restaurant_id', $employee->user_id)  // Filter by restaurant
            ->where('order_id', $orderId)
            ->firstOrFail();

        // Get the most recent payment for this order (for discount information)
        $payment = $order->payments()->latest()->first();

        // Add payment data to the order object for frontend compatibility
        if ($payment) {
            $order->discount_amount = $payment->discount_amount;
            $order->final_amount = $payment->final_amount;
            $order->payment_method = $payment->payment_method;
            $order->amount_paid = $payment->amount_paid;
            $order->paid_at = $payment->paid_at;
            $order->payment_notes = $payment->notes;
        }

        // Get restaurant details
        $restaurantData = \App\Models\Restaurant_Data::where('user_id', $employee->user_id)->first();

        // Handle temporary discount data from query parameters (legacy support)
        $tempDiscount = null;
        if ($request->has(['discount_percentage', 'discount_reason'])) {
            $discountPercentage = floatval($request->discount_percentage);
            $discountAmount = ($order->total_amount * $discountPercentage) / 100;

            $tempDiscount = [
                'percentage' => $discountPercentage,
                'amount' => $discountAmount,
                'reason' => $request->discount_reason,
                'notes' => $request->discount_notes ?? ''
            ];
        }

        // Return print-friendly HTML view
        return view('bills.print-bill', [
            'order' => $order,
            'restaurant' => $restaurantData,
            'payment' => $payment,
            'tempDiscount' => $tempDiscount,
        ]);
    }

    public function processPayment($orderId)
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            abort(403, 'Access denied. Cashiers only.');
        }

        // Get the specific order
        $order = CustomerOrder::with(['table', 'orderItems.dish', 'employee'])
            ->where('restaurant_id', $employee->user_id)
            ->where('order_id', $orderId)
            ->firstOrFail();

        return Inertia::render('Cashier/ProcessPayment', [
            'employee' => $employee->load('role'),
            'order' => $order,
        ]);
    }

    public function updatePaymentStatus(Request $request, $orderId)
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            abort(403, 'Access denied. Cashiers only.');
        }

        $request->validate([
            'payment_method' => 'required|in:cash,card,digital_wallet',
            'amount_paid' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        // Get the order
        $order = CustomerOrder::where('restaurant_id', $employee->user_id)
            ->where('order_id', $orderId)
            ->firstOrFail();

        // Calculate final amount
        $finalAmount = $order->total_amount - ($request->discount_amount ?? 0);
        $changeAmount = $request->amount_paid - $finalAmount;

        // Create payment record
        $paymentId = 'PAY_' . $orderId . '_' . time();

        CustomerPayment::create([
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'payment_method' => $request->payment_method,
            'original_amount' => $order->total_amount,
            'discount_amount' => $request->discount_amount ?? 0,
            'final_amount' => $finalAmount,
            'amount_paid' => $request->amount_paid,
            'change_amount' => $changeAmount > 0 ? $changeAmount : null,
            'status' => 'completed',
            'notes' => $request->notes,
            'cashier_id' => $employee->employee_id,
            'paid_at' => now(),
        ]);

        // Update order status to paid
        $order->update([
            'status' => 'paid',
        ]);

        return redirect()->route('cashier.bills')->with('success', 'Payment processed successfully!');
    }

    public function applyDiscount(Request $request, $orderId)
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            return response()->json(['error' => 'Access denied. Cashiers only.'], 403);
        }

        // Validate the request
        $request->validate([
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'discount_amount' => 'required|numeric|min:0',
            'discount_reason' => 'required|string|max:255',
            'discount_notes' => 'nullable|string|max:500',
        ]);

        // Find the order
        $order = CustomerOrder::findOrFail($orderId);

        // For now, allow any cashier to apply discounts to any order
        // TODO: Implement proper restaurant verification later

        try {
            // Create or update a pending payment record with discount information
            $paymentId = 'PAY_' . $orderId . '_' . time();

            // Check if there's already a pending payment for this order
            $existingPayment = CustomerPayment::where('order_id', $orderId)
                ->where('status', 'pending')
                ->first();

            if ($existingPayment) {
                // Update existing pending payment
                $existingPayment->update([
                    'discount_amount' => $request->discount_amount,
                    'final_amount' => $order->total_amount - $request->discount_amount,
                    'amount_paid' => $order->total_amount - $request->discount_amount,
                    'notes' => $request->discount_notes,
                ]);
                $payment = $existingPayment;
            } else {
                // Create new pending payment record with discount
                $payment = CustomerPayment::create([
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'payment_method' => 'cash', // Default, will be updated when payment is processed
                    'original_amount' => $order->total_amount,
                    'discount_amount' => $request->discount_amount,
                    'final_amount' => $order->total_amount - $request->discount_amount,
                    'amount_paid' => $order->total_amount - $request->discount_amount,
                    'status' => 'pending',
                    'notes' => $request->discount_notes,
                    'cashier_id' => $employee->employee_id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Discount applied successfully',
                'order' => $order->fresh(),
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to apply discount',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function removeDiscount(Request $request, $orderId)
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            return response()->json(['error' => 'Access denied. Cashiers only.'], 403);
        }

        // Find the order
        $order = CustomerOrder::findOrFail($orderId);

        // For now, allow any cashier to remove discounts from any order
        // TODO: Implement proper restaurant verification later

        try {
            // Find and remove pending payment record with discount
            $payment = CustomerPayment::where('order_id', $orderId)
                ->where('status', 'pending')
                ->first();

            if ($payment) {
                // Reset payment to original amount (remove discount)
                $payment->update([
                    'discount_amount' => 0,
                    'final_amount' => $order->total_amount,
                    'amount_paid' => $order->total_amount,
                    'notes' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Discount removed successfully',
                'order' => $order->fresh(),
                'payment' => $payment ? $payment->fresh() : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove discount',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}