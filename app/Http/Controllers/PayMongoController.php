<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerOrder;
use App\Models\CustomerPayment;
use Inertia\Inertia;

class PayMongoController extends Controller
{
    public function createCheckout(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:customer_orders,order_id',
            'amount' => 'required|numeric|min:1',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'method' => 'required|string|in:gcash,grab_pay,card',
        ]);

        // Get the order to verify amount
        $order = CustomerOrder::findOrFail($request->order_id);

        // Check for pending payment with discount information
        $pendingPayment = CustomerPayment::where('order_id', $request->order_id)
            ->where('status', 'pending')
            ->first();

        // Verify the amount matches the order total (allowing for discounts)
        $expectedAmount = $order->total_amount;
        $discountAmount = 0;

        if ($pendingPayment && $pendingPayment->discount_amount) {
            $discountAmount = $pendingPayment->discount_amount;
            $expectedAmount -= $discountAmount;
        }

        if (abs($request->amount - $expectedAmount) > 0.01) {
            Log::warning('Payment Amount Mismatch', [
                'order_id' => $request->order_id,
                'order_number' => $order->order_number,
                'received_amount' => $request->amount,
                'expected_amount' => $expectedAmount,
                'order_total' => $order->total_amount,
                'discount_amount' => $discountAmount,
                'difference' => abs($request->amount - $expectedAmount),
            ]);

            return response()->json([
                'error' => 'Amount mismatch',
                'details' => [
                    'received_amount' => $request->amount,
                    'expected_amount' => $expectedAmount,
                    'order_total' => $order->total_amount,
                    'discount_amount' => $discountAmount,
                ]
            ], 400);
        }

        try {
            $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
                ->post('https://api.paymongo.com/v1/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'line_items' => [
                                [
                                    'currency' => 'PHP',
                                    'amount' => (int) round($request->amount * 100), // Convert to centavos as integer
                                    'name' => 'Restaurant Order #' . $order->order_number,
                                    'quantity' => 1,
                                ],
                            ],
                            'payment_method_types' => [$request->method],
                            'success_url' => route('payment.success') . '?order_id=' . $request->order_id,
                            'cancel_url' => route('payment.failed') . '?order_id=' . $request->order_id,
                            'customer' => [
                                'name' => $request->customer_name,
                                'email' => $request->customer_email,
                            ],
                            'metadata' => [
                                'order_id' => $request->order_id,
                                'payment_method' => $request->method,
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('PayMongo Checkout Error', [
                    'response' => $response->json(),
                    'order_id' => $request->order_id,
                ]);
                return response()->json(['error' => 'Payment processing failed'], 400);
            }

            $checkoutData = $response->json();

            // Update existing pending payment or create new one
            $employee = Auth::guard('cashier')->user();

            if ($pendingPayment) {
                // Update existing pending payment record
                $pendingPayment->update([
                    'payment_method' => $request->method,
                    'checkout_session_id' => $checkoutData['data']['id'],
                    'updated_at' => now(),
                ]);
                $payment = $pendingPayment;
            } else {
                // Create new payment record if none exists
                $paymentId = 'PAY_' . $request->order_id . '_' . time();
                $payment = CustomerPayment::create([
                    'payment_id' => $paymentId,
                    'order_id' => $request->order_id,
                    'payment_method' => $request->method,
                    'original_amount' => $order->total_amount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $expectedAmount,
                    'amount_paid' => $expectedAmount,
                    'status' => 'pending',
                    'checkout_session_id' => $checkoutData['data']['id'],
                    'cashier_id' => $employee ? $employee->employee_id : null,
                ]);
            }

            // Log the checkout session for tracking
            Log::info('PayMongo Checkout Created', [
                'order_id' => $request->order_id,
                'checkout_session_id' => $checkoutData['data']['id'],
                'amount' => $request->amount,
                'method' => $request->method,
            ]);

            return response()->json([
                'checkout_url' => $checkoutData['data']['attributes']['checkout_url'],
                'checkout_session_id' => $checkoutData['data']['id'],
            ]);

        } catch (\Exception $e) {
            Log::error('PayMongo Exception', [
                'message' => $e->getMessage(),
                'order_id' => $request->order_id,
            ]);

            return response()->json([
                'error' => 'Payment processing error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function attachPaymentMethod(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'required|string',
        ]);

        try {
            $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
                ->post("https://api.paymongo.com/v1/payment_intents/{$request->payment_intent_id}/attach", [
                    'data' => [
                        'attributes' => [
                            'payment_method' => $request->payment_method_id,
                            'return_url' => route('payment.success'),
                        ],
                    ],
                ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('PayMongo Attach Payment Error', [
                'message' => $e->getMessage(),
                'payment_intent_id' => $request->payment_intent_id,
            ]);

            return response()->json([
                'error' => 'Payment processing error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $orderId = $request->query('order_id');
        $checkoutSessionId = $request->query('checkout_session_id');

        if ($orderId) {
            // Get the order and its payment
            $order = CustomerOrder::find($orderId);
            if ($order) {
                // Find the pending payment record
                $payment = CustomerPayment::where('order_id', $orderId)
                    ->where('status', 'pending')
                    ->first();

                if ($payment) {
                    // Update payment record to completed
                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'transaction_id' => $checkoutSessionId,
                        'payment_details' => [
                            'checkout_session_id' => $checkoutSessionId,
                            'payment_gateway' => 'paymongo',
                            'success_url_accessed' => now()->toISOString(),
                        ],
                    ]);

                    // Update order status to paid
                    $order->update([
                        'status' => 'paid',
                    ]);

                    Log::info('Order Payment Completed', [
                        'order_id' => $orderId,
                        'order_number' => $order->order_number,
                        'payment_id' => $payment->payment_id,
                        'original_amount' => $payment->original_amount,
                        'final_amount' => $payment->final_amount,
                        'discount_applied' => $payment->discount_amount,
                    ]);
                } else {
                    Log::warning('Payment Success but no pending payment found', [
                        'order_id' => $orderId,
                        'checkout_session_id' => $checkoutSessionId,
                    ]);
                }
            }
        }

        return Inertia::render('Cashier/PaymentSuccess', [
            'order' => $order ?? null,
            'payment' => $payment ?? null,
        ]);
    }

    public function paymentFailed(Request $request)
    {
        $orderId = $request->query('order_id');
        $order = null;
        $payment = null;

        if ($orderId) {
            $order = CustomerOrder::find($orderId);

            // Update payment record to failed
            $payment = CustomerPayment::where('order_id', $orderId)
                ->where('status', 'pending')
                ->first();

            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'payment_details' => [
                        'payment_gateway' => 'paymongo',
                        'failure_reason' => 'User cancelled or payment failed',
                        'failed_at' => now()->toISOString(),
                    ],
                ]);
            }

            Log::warning('Order Payment Failed', [
                'order_id' => $orderId,
                'order_number' => $order ? $order->order_number : 'Unknown',
                'payment_id' => $payment ? $payment->payment_id : null,
            ]);
        }

        return Inertia::render('Cashier/PaymentFailed', [
            'order' => $order,
            'payment' => $payment,
        ]);
    }
}