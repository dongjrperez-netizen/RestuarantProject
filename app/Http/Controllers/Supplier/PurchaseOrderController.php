<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
            ->whereIn('status', ['confirmed', 'partially_delivered'])
            ->findOrFail($id);

        // Force delivery_date to today so supplier cannot change it from the client
        $request->merge([
            'delivery_date' => now()->toDateString(),
        ]);

        $validator = Validator::make($request->all(), [
            'delivery_date' => 'required|date|before_or_equal:today',
            'delivery_type' => 'required|in:full,partial',
            'delivery_notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.purchase_order_item_id' => 'required_with:items|integer|exists:purchase_order_items,purchase_order_item_id',
            'items.*.delivered_quantity' => 'required_with:items|numeric|min:0',
        ]);

        // Additional per-item validation: delivered_quantity cannot exceed remaining quantity
        $validator->after(function ($validator) use ($purchaseOrder) {
            $data = $validator->getData();
            $itemsInput = $data['items'] ?? [];

            if (!is_array($itemsInput)) {
                return;
            }

            foreach ($itemsInput as $index => $itemData) {
                if (!isset($itemData['purchase_order_item_id']) || !array_key_exists('delivered_quantity', $itemData)) {
                    continue;
                }

                $item = $purchaseOrder->items()
                    ->where('purchase_order_item_id', $itemData['purchase_order_item_id'])
                    ->first();

                if (!$item) {
                    continue;
                }

                // supplier_delivered_quantity is cumulative total delivered by supplier
                $alreadyDelivered = $item->supplier_delivered_quantity ?? 0;
                $remaining = max($item->ordered_quantity - $alreadyDelivered, 0);

                if ($itemData['delivered_quantity'] > $remaining) {
                    $validator->errors()->add(
                        "items.$index.delivered_quantity",
                        'Delivered quantity cannot be greater than remaining quantity ('.$remaining.').'
                    );
                }
            }
        });

        $validated = $validator->validate();

        // Let supplier mark this order as fully or partially delivered.
        // Inventory and exact quantities are still handled on the restaurant side.
        $status = $validated['delivery_type'] === 'full'
            ? 'delivered'
            : 'partially_delivered';

        $updateData = [
            'status' => $status,
            'actual_delivery_date' => $validated['delivery_date'],
        ];

        if (isset($validated['delivery_notes'])) {
            $updateData['notes'] = ($purchaseOrder->notes ? $purchaseOrder->notes."\n\n" : '').
                'Delivery Note: '.$validated['delivery_notes'];
        }

        $purchaseOrder->update($updateData);

        // Persist supplier-delivered quantities per item (cumulative total delivered by supplier).
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                $item = $purchaseOrder->items()
                    ->where('purchase_order_item_id', $itemData['purchase_order_item_id'])
                    ->first();

                if ($item) {
                    $currentDelivered = $item->supplier_delivered_quantity ?? 0;
                    $item->update([
                        'supplier_delivered_quantity' => $currentDelivered + $itemData['delivered_quantity'],
                    ]);
                }
            }
        }

        return redirect()->back()
            ->with('success', 'Purchase order marked as delivered.');
    }
}
