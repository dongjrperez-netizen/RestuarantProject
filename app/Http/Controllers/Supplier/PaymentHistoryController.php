<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentHistoryController extends Controller
{
   public function index()
    {
        $supplier = Auth::guard('supplier')->user();

        $payments = SupplierPayment::where('supplier_id', $supplier->supplier_id)
            ->with([
                'bill.purchaseOrder' => function ($query) {
                    $query->select(
                        'purchase_order_id',
                        'po_number',
                        'total_amount',
                        'status'
                    );
                }
            ])
            ->orderBy('payment_date', 'desc')
            ->paginate(15) 
            ->through(function ($payment) {
                $purchaseOrder = $payment->bill->purchaseOrder ?? null;

                return [
                    'payment_reference' => $payment->payment_reference,
                    'payment_date' => $payment->payment_date,
                    'payment_method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    'payment_amount' => $payment->payment_amount,
                    'status' => ucfirst($payment->status),

                    'po_number' => $purchaseOrder->po_number ?? 'N/A',
                    'total_amount' => $purchaseOrder->total_amount ?? 0,
                    'po_status' => $purchaseOrder->status ?? 'N/A',

                    'balance' => $purchaseOrder
                        ? max(0, $purchaseOrder->total_amount - $payment->payment_amount)
                        : 0,
                ];
            });

        return Inertia::render('Supplier/PaymentHistory/History', [
            'supplier' => $supplier,
            'payments' => $payments,
        ]);
    }
}