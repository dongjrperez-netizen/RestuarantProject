<?php

namespace App\Http\Controllers;

use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
            'payment_method' => 'required|in:cash,bank_transfer,check,credit_card,online,other',
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
            'payment_method' => 'required|in:cash,bank_transfer,check,credit_card,online,other',
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
            'payment_method' => 'required|in:cash,bank_transfer,check,credit_card,paypal,online,other',
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
}
