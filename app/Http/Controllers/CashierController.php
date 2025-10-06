<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\CustomerPayment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

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
        $orders = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments', 'reservation'])
            ->where('restaurant_id', $employee->user_id)  // Filter by restaurant
            ->whereIn('status', ['pending', 'ready', 'completed','in_progress'])  // Exclude paid orders
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
        $order = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments', 'reservation'])
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

        // Get the specific order with all details including payments and reservation
        $order = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments', 'reservation'])
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
        \Log::info('Cash payment request received', [
            'order_id' => $orderId,
            'request_data' => $request->all(),
            'user_agent' => $request->userAgent()
        ]);

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

        // Get the order with reservation
        $order = CustomerOrder::with('reservation')
            ->where('restaurant_id', $employee->user_id)
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

        // Update table status to available
        if ($order->table) {
            $order->table->update([
                'status' => 'available'
            ]);
        }

        // Update reservation status to completed if order has reservation
        if ($order->reservation) {
            $order->reservation->update([
                'status' => 'completed'
            ]);

            \Log::info('Reservation marked as completed after payment', [
                'reservation_id' => $order->reservation->id,
                'order_id' => $orderId,
                'customer' => $order->reservation->customer_name,
            ]);
        }

        // Return JSON response for frontend to handle redirect
        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully!',
            'payment' => [
                'payment_id' => $paymentId,
                'final_amount' => $finalAmount,
                'amount_paid' => $request->amount_paid,
                'change_amount' => $changeAmount > 0 ? $changeAmount : 0,
                'payment_method' => $request->payment_method
            ],
            'redirect_url' => route('cashier.successful-payments')
        ]);
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

    public function payWithPaypal(Request $request)
    {
        \Log::info('PayPal payment request received', [
            'request_data' => $request->all(),
            'user_agent' => $request->userAgent()
        ]);

        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            return response()->json(['error' => 'Access denied. Cashiers only.'], 403);
        }

        $request->validate([
            'order_id' => 'required|exists:customer_orders,order_id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:paypal',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
        ]);

        // Get the order
        $order = CustomerOrder::where('restaurant_id', $employee->user_id)
            ->where('order_id', $request->order_id)
            ->firstOrFail();

        try {
            // Check if PayPal is configured
            $paypalConfig = config('paypal');
            if (empty($paypalConfig['sandbox']['client_id']) && empty($paypalConfig['live']['client_id'])) {
                throw new \Exception('PayPal credentials not configured. Please set PAYPAL_SANDBOX_CLIENT_ID and PAYPAL_SANDBOX_CLIENT_SECRET in your .env file.');
            }

            $provider = new PayPalClient;
            $provider->setApiCredentials($paypalConfig);
            $accessToken = $provider->getAccessToken();

            Log::info('PayPal client initialized successfully', [
                'mode' => $paypalConfig['mode'] ?? 'sandbox',
                'has_access_token' => !empty($accessToken),
            ]);

            $returnUrl = route('cashier.payment.paypal.success');
            $cancelUrl = route('cashier.payment.paypal.cancel');

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => config('paypal.currency', 'USD'),
                            'value' => number_format($request->amount, 2, '.', ''),
                        ],
                        'description' => "Payment for Order #{$order->order_number} - " . ($order->customer_name ?? 'Walk-in Customer'),
                    ]
                ],
                'application_context' => [
                    'cancel_url' => $cancelUrl,
                    'return_url' => $returnUrl,
                    'brand_name' => 'Restaurant Payment',
                    'locale' => 'en-US',
                    'landing_page' => 'BILLING',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                ]
            ];

            $response = $provider->createOrder($orderData);

            Log::info('PayPal createOrder Response', [
                'order_id' => $order->order_id,
                'paypal_order_id' => $response['id'] ?? 'missing',
                'response_status' => $response['status'] ?? 'missing',
                'links_count' => isset($response['links']) ? count($response['links']) : 0,
            ]);

            if (isset($response['id']) && $response['id'] != null) {
                // Find the approval URL
                $approvalUrl = null;
                foreach ($response['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        $approvalUrl = $link['href'];
                        break;
                    }
                }

                // Store payment details in session for processing after approval
                session([
                    'paypal_order_payment' => [
                        'order_id' => $order->order_id,
                        'paypal_order_id' => $response['id'],
                        'amount' => $request->amount,
                        'discount_amount' => $request->discount_amount,
                        'discount_reason' => $request->discount_reason,
                        'cashier_id' => $employee->employee_id,
                    ]
                ]);

                return response()->json([
                    'success' => true,
                    'approval_url' => $approvalUrl,
                    'paypal_order_id' => $response['id']
                ]);
            } else {
                Log::error('PayPal createOrder failed', [
                    'response' => $response,
                    'order_id' => $order->order_id
                ]);

                return response()->json([
                    'error' => 'Failed to create PayPal payment',
                    'details' => $response['message'] ?? 'Unknown error'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('PayPal payment error', [
                'order_id' => $order->order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'PayPal payment failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function paypalSuccess(Request $request)
    {
        $paypalOrderId = $request->get('token');
        $paymentData = session('paypal_order_payment');

        Log::info('PayPal success callback', [
            'paypal_order_id' => $paypalOrderId,
            'session_data' => $paymentData,
            'request_params' => $request->all()
        ]);

        if (!$paymentData) {
            Log::error('PayPal success: No payment data in session');
            return redirect()->route('cashier.bills')->with('error', 'Payment session expired');
        }

        // Verify the PayPal order ID matches
        if ($paymentData['paypal_order_id'] !== $paypalOrderId) {
            Log::error('PayPal success: Order ID mismatch', [
                'session_id' => $paymentData['paypal_order_id'],
                'callback_id' => $paypalOrderId
            ]);
            return redirect()->route('cashier.bills')->with('error', 'Payment verification failed');
        }

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            Log::info('PayPal Payment Capture', [
                'order_id' => $paymentData['order_id'],
                'paypal_order_id' => $paypalOrderId,
                'amount' => $paymentData['amount'],
                'currency' => config('paypal.currency'),
                'mode' => config('paypal.mode'),
            ]);

            // Capture the payment
            $response = $provider->capturePaymentOrder($paypalOrderId);

            Log::info('PayPal capture response', [
                'response_status' => $response['status'] ?? 'missing',
                'capture_status' => $response['purchase_units'][0]['payments']['captures'][0]['status'] ?? 'missing',
                'capture_id' => $response['purchase_units'][0]['payments']['captures'][0]['id'] ?? 'missing',
            ]);

            if (isset($response['status']) && $response['status'] === 'COMPLETED') {
                // Get the order
                $order = CustomerOrder::findOrFail($paymentData['order_id']);

                // Calculate final amount
                $finalAmount = $paymentData['amount'];
                $discountAmount = $paymentData['discount_amount'] ?? 0;

                // Extract PayPal transaction details
                $captureDetails = $response['purchase_units'][0]['payments']['captures'][0] ?? null;
                $transactionId = $captureDetails['id'] ?? $response['id'];
                $payerInfo = $response['payer'] ?? null;

                // Create payment record with PayPal transaction details
                $paymentId = 'PAY_' . $paymentData['order_id'] . '_' . time();

                CustomerPayment::create([
                    'payment_id' => $paymentId,
                    'order_id' => $paymentData['order_id'],
                    'payment_method' => 'paypal',
                    'original_amount' => $order->total_amount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'amount_paid' => $finalAmount,
                    'status' => 'completed',
                    'transaction_id' => $transactionId,
                    'checkout_session_id' => $paypalOrderId, // Store PayPal Order ID as checkout session
                    'payment_details' => json_encode([
                        'paypal_order_id' => $paypalOrderId,
                        'transaction_id' => $transactionId,
                        'capture_status' => $captureDetails['status'] ?? 'unknown',
                        'capture_amount' => $captureDetails['amount'] ?? null,
                        'payer_email' => $payerInfo['email_address'] ?? null,
                        'payer_name' => $payerInfo['name'] ?? null,
                        'create_time' => $captureDetails['create_time'] ?? null,
                        'update_time' => $captureDetails['update_time'] ?? null,
                        'full_response' => $response, // Store complete PayPal response for reference
                    ]),
                    'notes' => 'PayPal Payment - Transaction ID: ' . $transactionId,
                    'cashier_id' => $paymentData['cashier_id'],
                    'paid_at' => now(),
                ]);

                // Update order status to paid
                $order->update(['status' => 'paid']);

                // Update table status to available
                if ($order->table) {
                    $order->table->update([
                        'status' => 'available'
                    ]);
                }

                // Clear session data
                session()->forget('paypal_order_payment');

                return redirect()->route('cashier.successful-payments')
                    ->with('success', 'Payment completed successfully via PayPal');

            } else {
                Log::error('PayPal capture failed', [
                    'response' => $response,
                    'order_id' => $paymentData['order_id']
                ]);

                return redirect()->route('cashier.bills')->with('error', 'PayPal payment capture failed');
            }

        } catch (\Exception $e) {
            Log::error('PayPal success processing error', [
                'order_id' => $paymentData['order_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('cashier.bills')->with('error', 'PayPal payment processing failed: ' . $e->getMessage());
        }
    }

    public function paypalCancel(Request $request)
    {
        $paymentData = session('paypal_order_payment');

        Log::info('PayPal payment cancelled', [
            'session_data' => $paymentData,
            'request_params' => $request->all()
        ]);

        // Clear session data
        session()->forget('paypal_order_payment');

        $orderId = $paymentData['order_id'] ?? null;
        if ($orderId) {
            return redirect()->route('cashier.bills.view', $orderId)
                ->with('info', 'PayPal payment was cancelled');
        }

        return redirect()->route('cashier.bills')
            ->with('info', 'PayPal payment was cancelled');
    }

    public function paymentSuccess(Request $request)
    {
        // Get the authenticated employee
        $employee = Auth::guard('cashier')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'cashier') {
            abort(403, 'Access denied. Cashiers only.');
        }

        $orderId = $request->get('order_id');

        if (!$orderId) {
            return redirect()->route('cashier.bills')->with('error', 'Order ID is required');
        }

        // Get the specific order with all details
        $order = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments', 'reservation'])
            ->where('restaurant_id', $employee->user_id)
            ->where('order_id', $orderId)
            ->firstOrFail();

        // Get the most recent completed payment for this order
        $payment = $order->payments()->where('status', 'completed')->latest()->first();

        if (!$payment) {
            return redirect()->route('cashier.bills')->with('error', 'Payment not found');
        }

        return Inertia::render('Cashier/PaymentSuccess', [
            'employee' => $employee->load('role'),
            'order' => $order,
            'payment' => $payment,
        ]);
    }
}