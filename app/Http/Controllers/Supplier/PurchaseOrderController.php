<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $supplier = Auth::guard('supplier')->user();

        $purchaseOrders = PurchaseOrder::with(['restaurant' => function ($query) {
            $query->select('id', 'restaurant_name', 'contact_number', 'address');
        }, 'items.ingredient'])
            ->where('supplier_id', $supplier->supplier_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Supplier/PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function show($id)
    {
        $supplier = Auth::guard('supplier')->user();

        $purchaseOrder = PurchaseOrder::with([
            'restaurant' => function ($query) {
                $query->select('id', 'restaurant_name', 'contact_number', 'address');
            },
            'items.ingredient',
            'createdBy' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email');
            },
        ])
            ->where('supplier_id', $supplier->supplier_id)
            ->findOrFail($id);

        return Inertia::render('Supplier/PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $supplier = Auth::guard('supplier')->user();

        $purchaseOrder = PurchaseOrder::where('supplier_id', $supplier->supplier_id)
            ->whereIn('status', ['sent', 'pending'])
            ->findOrFail($id);

        $validated = $request->validate([
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'status' => 'confirmed',
        ];

        if (isset($validated['expected_delivery_date'])) {
            $updateData['expected_delivery_date'] = $validated['expected_delivery_date'];
        }

        if (isset($validated['notes'])) {
            $updateData['notes'] = ($purchaseOrder->notes ? $purchaseOrder->notes."\n\n" : '').
                'Supplier Note: '.$validated['notes'];
        }

        $purchaseOrder->update($updateData);

        return redirect()->back()
            ->with('success', 'Purchase order confirmed successfully.');
    }

    public function reject(Request $request, $id)
    {
        $supplier = Auth::guard('supplier')->user();

        $purchaseOrder = PurchaseOrder::where('supplier_id', $supplier->supplier_id)
            ->whereIn('status', ['sent', 'pending'])
            ->findOrFail($id);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $purchaseOrder->update([
            'status' => 'cancelled',
            'notes' => ($purchaseOrder->notes ? $purchaseOrder->notes."\n\n" : '').
                'Rejected by Supplier: '.$validated['rejection_reason'],
        ]);

        return redirect()->back()
            ->with('success', 'Purchase order rejected.');
    }

    public function markDelivered(Request $request, $id)
    {
        $supplier = Auth::guard('supplier')->user();

        $purchaseOrder = PurchaseOrder::where('supplier_id', $supplier->supplier_id)
            ->where('status', 'confirmed')
            ->findOrFail($id);

        $validated = $request->validate([
            'delivery_date' => 'required|date|before_or_equal:today',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'status' => 'delivered',
            'actual_delivery_date' => $validated['delivery_date'],
        ];

        if (isset($validated['delivery_notes'])) {
            $updateData['notes'] = ($purchaseOrder->notes ? $purchaseOrder->notes."\n\n" : '').
                'Delivery Note: '.$validated['delivery_notes'];
        }

        $purchaseOrder->update($updateData);

        return redirect()->back()
            ->with('success', 'Purchase order marked as delivered.');
    }
}
