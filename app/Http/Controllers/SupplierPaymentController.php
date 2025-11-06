<?php

namespace App\Http\Controllers;

use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Str;

class SupplierPaymentController extends Controller
{
    public function index()
    {
        $restaurantId = auth()->user()->restaurantData->id;

        $payments = SupplierPayment::with(['supplier', 'bill', 'createdBy'])
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total_payments' => $payments->sum('payment_amount'),
            'payment_count' => $payments->count(),
            'this_month_payments' => $payments->where('payment_date', '>=', now()->startOfMonth())->sum('payment_amount'),
        ];

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'summary' => $summary,
        ]);
    }

    public function show($id)
    {
        $payment = SupplierPayment::with([
            'supplier',
            'bill.purchaseOrder',
            'createdBy',
        ])->findOrFail($id);

        return Inertia::render('Payments/Show', [
            'payment' => $payment,
        ]);
    }

    public function create($billId = null)
    {
        $restaurantId = auth()->user()->restaurantData->id;

        $bills = SupplierBill::with('supplier')
            ->where('restaurant_id', $restaurantId)
            ->where('outstanding_amount', '>', 0)
            ->whereIn('status', ['pending', 'overdue', 'partially_paid'])
            ->orderBy('due_date')
            ->get();

        $selectedBill = null;
        if ($billId) {
            $selectedBill = SupplierBill::with('supplier')->findOrFail($billId);
        }

        return Inertia::render('Payments/Create', [
            'bills' => $bills,
            'selectedBill' => $selectedBill,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_id' => 'required|exists:supplier_bills,bill_id',
            'payment_date' => 'required|date',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,gcash,check,bank_transfer,credit_card,paypal,online,other',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $bill = SupplierBill::findOrFail($validated['bill_id']);

        if ($validated['payment_amount'] > $bill->outstanding_amount) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Payment amount cannot exceed outstanding amount.');
        }

        try {
            DB::beginTransaction();

            $payment = SupplierPayment::create([
                'bill_id' => $validated['bill_id'],
                'restaurant_id' => auth()->user()->restaurantData->id,
                'supplier_id' => $bill->supplier_id,
                'payment_date' => $validated['payment_date'],
                'payment_amount' => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'],
                'transaction_reference' => $validated['transaction_reference'],
                'notes' => $validated['notes'],
                'created_by_user_id' => auth()->id(),
                'status' => 'completed',
            ]);

            $newPaidAmount = $bill->paid_amount + $validated['payment_amount'];
            $newOutstandingAmount = $bill->total_amount - $newPaidAmount;

            $newStatus = 'pending';
            if ($newOutstandingAmount <= 0) {
                $newStatus = 'paid';
            } elseif ($newPaidAmount > 0) {
                $newStatus = 'partially_paid';
            }

            $bill->update([
                'paid_amount' => $newPaidAmount,
                'outstanding_amount' => $newOutstandingAmount,
                'status' => $newStatus,
            ]);

            DB::commit();

            return redirect()->route('payments.show', $payment->payment_id)
                ->with('success', 'Payment recorded successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error recording payment: '.$e->getMessage());
        }
    }

    public function edit($id)
    {
        $payment = SupplierPayment::with(['bill.supplier'])->findOrFail($id);

        if ($payment->status === 'cancelled') {
            return redirect()->route('payments.show', $id)
                ->with('error', 'Cannot edit cancelled payments.');
        }

        return Inertia::render('Payments/Edit', [
            'payment' => $payment,
        ]);
    }

    public function update(Request $request, $id)
    {
        $payment = SupplierPayment::with('bill')->findOrFail($id);

        if ($payment->status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'Cannot update cancelled payments.');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,gcash,check,bank_transfer,credit_card,paypal,online,other',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $bill = $payment->bill;
        $oldPaymentAmount = $payment->payment_amount;
        $newPaymentAmount = $validated['payment_amount'];

        $availableAmount = $bill->outstanding_amount + $oldPaymentAmount;
        if ($newPaymentAmount > $availableAmount) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Payment amount cannot exceed available amount.');
        }

        try {
            DB::beginTransaction();

            $payment->update([
                'payment_date' => $validated['payment_date'],
                'payment_amount' => $newPaymentAmount,
                'payment_method' => $validated['payment_method'],
                'transaction_reference' => $validated['transaction_reference'],
                'notes' => $validated['notes'],
            ]);

            $newPaidAmount = $bill->paid_amount - $oldPaymentAmount + $newPaymentAmount;
            $newOutstandingAmount = $bill->total_amount - $newPaidAmount;

            $newStatus = 'pending';
            if ($newOutstandingAmount <= 0) {
                $newStatus = 'paid';
            } elseif ($newPaidAmount > 0) {
                $newStatus = 'partially_paid';
            }

            $bill->update([
                'paid_amount' => $newPaidAmount,
                'outstanding_amount' => $newOutstandingAmount,
                'status' => $newStatus,
            ]);

            DB::commit();

            return redirect()->route('payments.show', $payment->payment_id)
                ->with('success', 'Payment updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating payment: '.$e->getMessage());
        }
    }

    public function cancel($id)
    {
        $payment = SupplierPayment::with('bill')->findOrFail($id);

        if ($payment->status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'Payment is already cancelled.');
        }

        try {
            DB::beginTransaction();

            $payment->update(['status' => 'cancelled']);

            $bill = $payment->bill;
            $newPaidAmount = $bill->paid_amount - $payment->payment_amount;
            $newOutstandingAmount = $bill->total_amount - $newPaidAmount;

            $newStatus = 'pending';
            if ($newOutstandingAmount <= 0) {
                $newStatus = 'paid';
            } elseif ($newPaidAmount > 0) {
                $newStatus = 'partially_paid';
            }

            if ($bill->due_date < now() && $newOutstandingAmount > 0) {
                $newStatus = 'overdue';
            }

            $bill->update([
                'paid_amount' => $newPaidAmount,
                'outstanding_amount' => $newOutstandingAmount,
                'status' => $newStatus,
            ]);

            DB::commit();

            return redirect()->route('payments.show', $payment->payment_id)
                ->with('success', 'Payment cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Error cancelling payment: '.$e->getMessage());
        }
    }

    public function getBillDetails($billId)
    {
        $bill = SupplierBill::with('supplier')->findOrFail($billId);

        return response()->json([
            'bill' => $bill,
            'supplier' => $bill->supplier,
            'outstanding_amount' => $bill->outstanding_amount,
        ]);
    }

    /**
     * Record a payment for a bill (API endpoint for billing routes)
     */
    public function recordPayment(Request $request)
    {
        $validated = $request->validate([
            'bill_id' => 'required|exists:supplier_bills,bill_id',
            'payment_date' => 'required|date',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,gcash,check,bank_transfer,credit_card,paypal,online,other',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'created_by_user_id' => 'nullable|exists:users,id',
        ]);

        $bill = SupplierBill::findOrFail($validated['bill_id']);

        if ($validated['payment_amount'] > $bill->outstanding_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed outstanding amount.',
                'errors' => ['payment_amount' => ['Payment amount cannot exceed outstanding amount.']],
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment = SupplierPayment::create([
                'bill_id' => $validated['bill_id'],
                'restaurant_id' => $bill->restaurant_id,
                'supplier_id' => $bill->supplier_id,
                'payment_date' => $validated['payment_date'],
                'payment_amount' => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'],
                'transaction_reference' => $validated['transaction_reference'],
                'notes' => $validated['notes'],
                'created_by_user_id' => $validated['created_by_user_id'] ?? auth()->id(),
                'status' => 'completed',
            ]);

            // Update bill status and amounts
            $newPaidAmount = $bill->paid_amount + $validated['payment_amount'];
            $newOutstandingAmount = $bill->total_amount - $newPaidAmount;

            $newStatus = 'pending';
            if ($newOutstandingAmount <= 0) {
                $newStatus = 'paid';
            } elseif ($newPaidAmount > 0) {
                $newStatus = 'partially_paid';
            }

            $bill->update([
                'paid_amount' => $newPaidAmount,
                'outstanding_amount' => $newOutstandingAmount,
                'status' => $newStatus,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'data' => [
                    'payment' => $payment->fresh(['bill', 'supplier', 'createdBy']),
                    'bill' => $bill->fresh(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error recording payment: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create PayMongo GCash checkout for supplier bill payment
     */
    public function createGCashCheckout(Request $request)
    {
        $validated = $request->validate([
            'bill_id' => 'required|exists:supplier_bills,bill_id',
            'payment_amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $bill = SupplierBill::with('supplier')->findOrFail($validated['bill_id']);

        // Verify the amount doesn't exceed outstanding amount
        if ($validated['payment_amount'] > $bill->outstanding_amount) {
            return response()->json([
                'error' => 'Payment amount cannot exceed outstanding amount',
                'details' => [
                    'payment_amount' => $validated['payment_amount'],
                    'outstanding_amount' => $bill->outstanding_amount,
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
                                    'amount' => (int) round($validated['payment_amount'] * 100),
                                    'name' => 'Supplier Bill #' . $bill->bill_number,
                                    'quantity' => 1,
                                ],
                            ],
                            'payment_method_types' => ['gcash'],
                            'success_url' => route('bills.gcash.success') . '?bill_id=' . $bill->bill_id . '&session_id=' . time(),
                            'cancel_url' => route('bills.gcash.failed') . '?bill_id=' . $bill->bill_id,
                            'description' => 'Payment for ' . $bill->supplier->supplier_name,
                            'metadata' => [
                                'bill_id' => $bill->bill_id,
                                'payment_amount' => $validated['payment_amount'],
                                'notes' => $validated['notes'] ?? '',
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('PayMongo GCash Checkout Error', [
                    'response' => $response->json(),
                    'bill_id' => $bill->bill_id,
                ]);
                return response()->json(['error' => 'Payment processing failed'], 400);
            }

            $checkoutData = $response->json();
            $checkoutSessionId = $checkoutData['data']['id'];

            // Create pending payment record
            $payment = SupplierPayment::create([
                'bill_id' => $bill->bill_id,
                'restaurant_id' => $bill->restaurant_id,
                'supplier_id' => $bill->supplier_id,
                'payment_date' => now(),
                'payment_amount' => $validated['payment_amount'],
                'payment_method' => 'gcash',
                'transaction_reference' => $checkoutSessionId,
                'notes' => $validated['notes'] ?? '',
                'created_by_user_id' => auth()->id(),
                'status' => 'pending', // Mark as pending until confirmed
            ]);

            Log::info('PayMongo GCash Checkout Created', [
                'bill_id' => $bill->bill_id,
                'checkout_session_id' => $checkoutSessionId,
                'payment_id' => $payment->payment_id,
                'amount' => $validated['payment_amount'],
            ]);

            return response()->json([
                'checkout_url' => $checkoutData['data']['attributes']['checkout_url'],
                'checkout_session_id' => $checkoutSessionId,
            ]);

        } catch (\Exception $e) {
            Log::error('PayMongo GCash Exception', [
                'message' => $e->getMessage(),
                'bill_id' => $bill->bill_id,
            ]);

            return response()->json([
                'error' => 'Payment processing error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle GCash payment success callback
     */
    public function gcashSuccess(Request $request)
    {
        $billId = $request->query('bill_id');

        Log::info('GCash Success Callback', [
            'bill_id' => $billId,
            'all_params' => $request->all(),
        ]);

        if (!$billId) {
            Log::error('GCash Success: Missing bill_id');
            return redirect()->route('bills.index')
                ->with('error', 'Payment processed but bill ID is missing.');
        }

        $bill = SupplierBill::find($billId);

        if (!$bill) {
            Log::error('GCash Success: Bill not found', ['bill_id' => $billId]);
            return redirect()->route('bills.index')
                ->with('error', 'Bill not found.');
        }

        // Find the pending payment record for this bill
        $payment = SupplierPayment::where('bill_id', $billId)
            ->where('status', 'pending')
            ->where('payment_method', 'gcash')
            ->latest()
            ->first();

        if (!$payment) {
            Log::error('GCash Success: No pending payment found', ['bill_id' => $billId]);
            return redirect()->route('bills.show', $billId)
                ->with('error', 'No pending payment found.');
        }

        try {
            DB::beginTransaction();

            // Update payment to completed
            $payment->update([
                'status' => 'completed',
            ]);

            // Update bill amounts and status
            $newPaidAmount = $bill->paid_amount + $payment->payment_amount;
            $newOutstandingAmount = $bill->total_amount - $newPaidAmount;

            $newStatus = 'pending';
            if ($newOutstandingAmount <= 0) {
                $newStatus = 'paid';
            } elseif ($newPaidAmount > 0) {
                $newStatus = 'partially_paid';
            }

            $bill->update([
                'paid_amount' => $newPaidAmount,
                'outstanding_amount' => $newOutstandingAmount,
                'status' => $newStatus,
            ]);

            DB::commit();

            Log::info('GCash Payment Completed', [
                'bill_id' => $billId,
                'payment_id' => $payment->payment_id,
                'amount' => $payment->payment_amount,
                'new_status' => $newStatus,
                'new_paid' => $newPaidAmount,
                'new_outstanding' => $newOutstandingAmount,
            ]);

            return redirect()->route('bills.show', $billId)
                ->with('success', 'Payment completed successfully via GCash!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('GCash Payment Success Handler Error', [
                'message' => $e->getMessage(),
                'bill_id' => $billId,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('bills.show', $billId)
                ->with('error', 'Error processing payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle GCash payment failure callback
     */
    public function gcashFailed(Request $request)
    {
        $billId = $request->query('bill_id');

        Log::warning('GCash Payment Cancelled/Failed', [
            'bill_id' => $billId,
        ]);

        return redirect()->route('bills.show', $billId)
            ->with('error', 'GCash payment was cancelled or failed. Please try again.');
    }


     /**
     * Handle Cash payment failure callback
     */
   public function handleCashPayment(Request $request)
{
    $validated = $request->validate([
        'bill_id' => 'required|exists:supplier_bills,bill_id',
        'payment_amount' => 'required|numeric|min:0.01',
        'payment_date' => 'required|date',
        'notes' => 'nullable|string|max:500',
    ]);

    $bill = SupplierBill::findOrFail($validated['bill_id']);

    if ($validated['payment_amount'] > $bill->outstanding_amount) {
        return response()->json([
            'success' => false,
            'message' => 'Payment amount cannot exceed outstanding amount.',
        ], 422);
    }

    try {
        DB::beginTransaction();

        // ✅ Generate unique payment reference safely (no infinite loop)
        $latestPayment = SupplierPayment::whereNotNull('payment_reference')
            ->orderByDesc('payment_id')
            ->first();

        if ($latestPayment && preg_match('/\d+$/', $latestPayment->payment_reference)) {
            $nextNumber = intval(substr($latestPayment->payment_reference, -6)) + 1;
        } else {
            $nextNumber = 1;
        }

        $paymentReference = 'PAY-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Double-check uniqueness just once
        if (SupplierPayment::where('payment_reference', $paymentReference)->exists()) {
            $paymentReference .= '-' . strtoupper(Str::random(3));
        }

        // ✅ Create the cash payment
        $payment = SupplierPayment::create([
            'bill_id' => $bill->bill_id,
            'restaurant_id' => $bill->restaurant_id,
            'supplier_id' => $bill->supplier_id,
            'payment_date' => $validated['payment_date'],
            'payment_amount' => $validated['payment_amount'],
            'payment_method' => 'cash',
            'transaction_reference' => 'CASH-' . strtoupper(uniqid()),
            'payment_reference' => $paymentReference,
            'notes' => $validated['notes'] ?? '',
            'created_by_user_id' => auth()->id(),
            'status' => 'completed',
        ]);

        // ✅ Update bill totals and status
        $newPaidAmount = $bill->paid_amount + $validated['payment_amount'];
        $newOutstandingAmount = $bill->total_amount - $newPaidAmount;

        $newStatus = 'pending';
        if ($newOutstandingAmount <= 0) {
            $newStatus = 'paid';
        } elseif ($newPaidAmount > 0) {
            $newStatus = 'partially_paid';
        }

        $bill->update([
            'paid_amount' => $newPaidAmount,
            'outstanding_amount' => $newOutstandingAmount,
            'status' => $newStatus,
        ]);

        DB::commit();

       return response()->json([
            'success' => true,
            'message' => 'Cash payment recorded successfully!',
            'redirect_url' => route('bills.show', $bill->bill_id),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        if ($request->expectsJson() === false) {
            return redirect()->back()->with('error', 'Error processing cash payment: ' . $e->getMessage());
        }

        return response()->json([
            'success' => false,
            'message' => 'Error processing cash payment: ' . $e->getMessage(),
        ], 500);
    }
}

}